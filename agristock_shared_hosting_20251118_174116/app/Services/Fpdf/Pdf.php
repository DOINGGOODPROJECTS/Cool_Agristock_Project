<?php
    namespace App\Services\Fpdf;

    use Codedge\Fpdf\Fpdf\Fpdf;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
    use Imagick;

    class Pdf extends Fpdf
    {
        // En-tête
        function Header()
        {
            // Logo
            $this->image('images/logo.png', 10, 4, 20);
            
            $this->setX(10);
            $this->setFont('Arial', 'B', 18);
            $this->cell(180, 6, utf8_decode("COOL AGRISTOCK"), 'C', 0, 'C');
            
            $path = base_path('images/qrcode.svg');
            
            // Générer le QR code au format SVG
            QrCode::margin(4)
                ->backgroundColor(211,211,211, 80)
                ->size(100)
                ->generate(url()->current(), $path);
            // Conversing from svg to png format
            $png = new Imagick();
            $png->readImageBlob(file_get_contents($path));
            $png->writeImages('qrcode.png', false);
            $png->resizeImage(80, 80, imagick::FILTER_LANCZOS, 1); 
            
            $this->Image($png->getImageFilename(), 186, 3, 14, 0);
            
            // Police Arial gras 15
            $this->setFont('Arial', 'B', 15);
            $this->Ln(7);
            // Décalage à droite
            $this->cell(190, 0, '', 1);
            // Titre
            // Saut de ligne
            $this->Ln(4);
        }

        // Pied de page
        function Footer()
        {
            // Positionnement à 1,5 cm du bas
            $this->setXY(40, -15);
            
            // Police Arial italique 8
            // Numéro de page
            $this->setFont('Arial', 'IB', 8);
            $this->cell(130, 2,'Page '.$this->PageNo(), '', 1,'C');
            $this->setXY(40, $this->getY()+3);
            $this->setFont('Arial', 'I', 6.5);
            $this->cell(130, 3, utf8_decode("SIS A LA RIVIERA FAYA - ROND POINT CITE SIR - COCODY"), '', 1, 'C');
            $this->setX(40);
            $this->cell(130, 3, utf8_decode("Abidjan - Côte d'Ivoire"), '', 1, 'C');
            $this->setX(40);
            $this->setFont('Arial', 'IB', 6.5);
            $this->cell(130, 3, utf8_decode("Tél: (+225) 0102030405 - Site Web: www.cool-agristock.com"), '', 1, 'C');
            // Flag Guinea
        }

        function invoiceTable($headers, $stock)
        {
            // Couleurs, épaisseur du trait et police grasse
            $this->setFillColor(78, 122, 223);
            $this->setTextColor(255);
            $this->setDrawColor(0, 0, 0);
            $this->setLineWidth(.3);
            $this->setFont('', 'IB', 7);
            
            // En-tête
            $w = [10, 45, 35, 25, 45, 30];
            for($i=0; $i < count($headers); $i++)
                $this->cell($w[$i], 7, utf8_decode($headers[$i]), 1, 0, 'C', true);
            $this->Ln();
            
            // Restauration des couleurs et de la police
            $this->setFillColor(229, 235, 250);
            $this->setTextColor(0);
            $this->setLineWidth(.1);
            // Données
            $fill = false;
            $this->setFont('Arial', 'I', 9);
            $sum = 0;
            foreach($stock->details->where('qty', '>', 0) as $key => $item)
            {
                $this->cell($w[0], 6, ++$key, 'LR', 0, 'C', $fill);
                $this->cell($w[1], 6, utf8_decode($item->product->name), 'LR', 0, 'C', $fill);
                $this->cell($w[2], 6, utf8_decode($item->container->name), 'LR', 0, 'C', $fill);
                $this->cell($w[3], 6, utf8_decode($item->qty." Kg"), 'LR', 0, 'C', $fill);
                $this->cell($w[4], 6, utf8_decode(''), 'LR', 0, 'C', $fill);
                $this->cell($w[5], 6, utf8_decode(moneyFormat(getPrice($item))), 'LR', 0, 'C', $fill);
                $this->Ln();
                $fill = !$fill;
                $sum += getPrice($item);
            }
            // Trait de terminaison
            $this->cell(array_sum($w), 0, '', 'T', 1);
            $this->Ln(5);

            // Totaux
            $this->setFont('Arial', 'B', 10);
            $this->setX(105);
            $this->cell(95, 7, utf8_decode('Total HT : '.moneyFormat($sum)), 0, 1, 'R');
            $this->setX(105);
            $this->cell(95, 7, utf8_decode('Total TVA : '.moneyFormat($sum)), 0, 1, 'R');
            $this->Ln(5);

            // Signatures
            $this->cell(95, 7, utf8_decode('Livreur / Fournisseur'), 0, 0);
            $this->cell(95, 7, utf8_decode('Réceptionnaire'), 0, 1, 'R');

            $this->setFont('Arial', '', 10);
            $this->cell(95, 10, utf8_decode('Nom : ___________________'), 0, 0);
            $this->cell(95, 10, utf8_decode('Nom : ___________________'), 0, 1, 'R');

            $this->cell(95, 10, utf8_decode('Signature : _______________'), 0, 0);
            $this->cell(95, 10, utf8_decode('Signature : _______________'), 0, 1, 'R');

            $this->cell(95, 10, utf8_decode('Mobile : _________________'), 0, 0);
            $this->cell(95, 10, utf8_decode('Mobile : _________________'), 0, 1, 'R');
            
        }
    }