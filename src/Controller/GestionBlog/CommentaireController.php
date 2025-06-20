<?php

namespace App\Controller\GestionBlog;

use App\Entity\Commentaire;
use App\Form\CommFormType;
use App\Form\GestionBlog\CommFormType as GestionBlogCommFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/comments')]
class CommentaireController extends AbstractController
{
    #[Route('/{id}/edit', name: 'comment_edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    Commentaire $comment,
    EntityManagerInterface $em,
    HttpClientInterface $httpClient
): Response {
    // Ensure blocked comments cannot be edited
    if ($comment->isBlocked()) {
        $this->addFlash('error', 'Blocked comments cannot be edited.');
        return $this->redirectToRoute('view_post', ['id' => $comment->getPost()->getId()]);
    }

    // Create form
    $form = $this->createForm(GestionBlogCommFormType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        try {
            $commContent = $comment->getCommContent();
            error_log("Comment being edited: " . $commContent); // Debugging log

            // ✅ Send AI evaluation request
                $response = $httpClient->request('POST', 'http://127.0.0.1:8000/evaluate_comment', [
                    'json' => ['comment' => $commContent],
                    'timeout' => 5
                ]);
                $evaluationData = $response->toArray();

            // ✅ Update comment properties
            $comment->setEvaluationScore((int)($evaluationData['positivity'] * 100));
            $comment->setIsBlocked($evaluationData['toxicity'] > 0.4);

            if (!$comment->isBlocked()) {
                $em->flush();
                $this->addFlash('success', 'Comment updated successfully!');
                return $this->redirectToRoute('view_post', ['id' => $comment->getPost()->getId()]);
            } else {
                $this->addFlash('error', 'Comment blocked due to high toxicity score. Please revise your message.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error updating comment: ' . $e->getMessage());
            error_log('Comment edit error: ' . $e->getMessage());
        }
    }

    return $this->render('GestionBlog/Comment/edit_comment.html.twig', [
        'form' => $form->createView(),
        'comment' => $comment,
    ]);
}



    #[Route('/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $comment, EntityManagerInterface $em): Response
    {
        $post = $comment->getPost();

        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $em->remove($comment);
            $em->flush();

            // ✅ Update comment count after deletion
            $post->setNbreComments((string) $post->getComments()->count());
            $em->flush();

            $this->addFlash('success', 'Comment deleted successfully!');
        }

        return $this->redirectToRoute('view_post', ['id' => $post->getId()]);
    }
}
