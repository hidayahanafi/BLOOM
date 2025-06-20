<?php

namespace App\Controller\GestionBlog;

use App\Entity\Commentaire;
use App\Entity\Post;
use App\Form\FormPosteType;
use App\Form\GestionBlog\CommFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


final class MainController extends AbstractController
{
    #[Route('/home', name: 'app_main')]
    public function index(Request $request, ManagerRegistry $doctrine, Security $security): Response
    {
        $user = $security->getUser(); // Get the logged-in user

        if (!$user) {
            $this->addFlash('error', 'You need to log in to create a post.');
            return $this->redirectToRoute('app_login');
        }

        $em = $doctrine->getManager();
        $post = new Post();
        $form = $this->createForm(FormPosteType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Log validation errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log('Post form errors: ' . implode(', ', $errors));
                $this->addFlash('error', 'Form validation failed: ' . implode(', ', $errors));
            } else {
                $post->setDatePublication(new \DateTime());
                $post->setNbreVues(0);
                $post->setNbreComments(0);
                $post->setStatut('valid');
                $post->setUser($user);

                $em->persist($post);
                $em->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => true, 'message' => 'Post created successfully!']);
                }

                return $this->redirectToRoute('app_main');
            }
        }
        $posts = $em->getRepository(Post::class)->findAll();
        return $this->render('GestionBlog/blog_grid.html.twig', [
            'postForm' => $form->createView(),
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}', name: 'view_post')]
public function viewPost(
    Request $request, 
    Post $post, 
    ManagerRegistry $doctrine, 
    HttpClientInterface $httpClient, 
    Security $security
): Response {
    $em = $doctrine->getManager();

    // ✅ Increase the post view count
    $post->setNbreVues($post->getNbreVues() + 1);
    $em->flush();

    // ✅ Create a new Comment entity
    $comment = new Commentaire();
    $form = $this->createForm(CommFormType::class, $comment);
    $form->handleRequest($request);

    // ✅ Check if the form is submitted
    if ($form->isSubmitted()) {
        // ✅ If form is invalid, log and display validation errors
        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Form validation failed: ' . implode(', ', $errors));
            error_log('Comment form errors: ' . implode(', ', $errors));
        } else {
            try {
                $user = $security->getUser(); // ✅ Get logged-in user
                if (!$user) {
                    throw new \Exception('User is not logged in.');
                }

                $commContent = $comment->getCommContent();
                error_log("New comment submitted: " . $commContent); // ✅ Debugging log

                // ✅ AI evaluation request (optional)
                try {
                    $response = $httpClient->request('POST', 'http://127.0.0.1:8000/evaluate_comment', [
                        'json' => ['comment' => $commContent],
                        'timeout' => 5
                    ]);
                    $evaluationData = $response->toArray();
                    
                } catch (\Exception $e) {
                    $evaluationData = ['positivity' => 0.5, 'toxicity' => 0]; // ✅ Default values if AI request fails
                    error_log('AI evaluation failed: ' . $e->getMessage());
                }

                // ✅ Set comment properties
                $comment->setCommContent($commContent);
                $comment->setUser($user);
                $comment->setPost($post);
                $comment->setCommDate(new \DateTime());
                $comment->setEvaluationScore((int)($evaluationData['positivity'] * 100));
                $comment->setIsBlocked($evaluationData['toxicity'] > 0.4);

                if (!$comment->isBlocked()) {
                    $em->persist($comment);
                    $em->flush();

                    // ✅ Update post comment count
                    $post->setNbreComments($post->getNbreComments() + 1);
                    $em->flush();

                    $this->addFlash('success', 'Comment added successfully!');
                    return $this->redirectToRoute('view_post', ['id' => $post->getId()]);
                } else {
                    $this->addFlash('error', 'Comment blocked due to high toxicity score. Please revise your message.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error processing comment: ' . $e->getMessage());
                error_log('Comment error: ' . $e->getMessage());
            }
        }
    }

    return $this->render('GestionBlog/blog_detail.html.twig', [
        'post' => $post,
        'form' => $form->createView(),
    ]);

}
#[Route('/post/edit/{id}', name: 'edit_post', methods: ['GET', 'POST'])]
    public function editPost(Request $request, ManagerRegistry $doctrine, SluggerInterface $slugger, int $id): Response
    {
        $em = $doctrine->getManager();
        $post = $em->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post not found');
        }

        $originalImage = $post->getImagePost();
        $form = $this->createForm(FormPosteType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_post')->getData();

            if ($imageFile) {
                if ($originalImage) {
                    $oldFilename = pathinfo($originalImage, PATHINFO_BASENAME);
                    @unlink($this->getParameter('images_directory') . '/' . $oldFilename);
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('images_directory'), $newFilename);
                    $imageUrl = $this->getParameter('images_base_url') . '/' . $newFilename;
                    $post->setImagePost($imageUrl);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload new image.');
                }
            } else {
                $post->setImagePost($originalImage);
            }

            $post->setDatePublication(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Post updated successfully!');
            return $this->redirectToRoute('app_main');
        }

        return $this->render('GestionBlog/edit_post.html.twig', [
            'postForm' => $form->createView(),
            'post' => $post,
            
        ]);
    }

    #[Route('/post/delete/{id}', name: 'delete_post', methods: ['POST', 'GET'])]
    public function deletePost(Post $post, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        
        try {
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting post: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_blog');
    }

    #[Route('/blog_grid', name: 'app_blog')]
    public function blogGrid(ManagerRegistry $doctrine): Response
    {
        $posts = $doctrine->getRepository(Post::class)->findAll();
        return $this->render('GestionBlog/blog_grid.html.twig', [
            'posts' => $posts,
        ]);
    }


}