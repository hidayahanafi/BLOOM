<?php

namespace App\Service;

use TCPDF;

class PdfGenerator
{
    public function generatePdf(string $content): string
    {
        // Créer une instance de TCPDF
        $pdf = new TCPDF();

        // Ajouter une page
        $pdf->AddPage();

        // Définir la police
        $pdf->SetFont('Helvetica', '', 12);

        // Ajouter le contenu
        $pdf->Write(0, $content);

        // Retourner le PDF en tant que chaîne (tu peux aussi le sauvegarder dans un fichier si tu préfères)
        return $pdf->Output('document.pdf', 'S'); // 'S' signifie retourner le fichier au format string
    }
}
