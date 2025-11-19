<?php

namespace App\Http\Controllers;

use App\Services\Fpdf\Pdf;
use App\Models\Stock;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    private static $obj;

    public function __construct()
    {
        self::$obj = new Pdf('P','mm','A4');
    }

    public function invoice(string $id)
    {
        $stock = Stock::find($id);
                    
        self::$obj->setTitle(utf8_decode("Facture - ".$stock->ref));
        // Informations générales
        self::$obj->AddPage();
        self::$obj->SetFont('Arial', '', 10);

        self::$obj->cell(50, 5, utf8_decode('N° : '.strtoupper($stock->ref)), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Date : '.date('d/m/Y', strtotime($stock->created_at))), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Heure de Réception : '.date('H:i', strtotime($stock->created_at))), 0, 1);
        self::$obj->Ln(5);

        self::$obj->cell(50, 5, utf8_decode('Nom du client : '.strtoupper($stock->customer->name)), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Contact (Téléphone) : '.strtoupper($stock->customer->phone)), 0, 1);
        self::$obj->Ln(5);

        self::$obj->cell(50, 5, utf8_decode("Nom de l'espace de stockage : ".strtoupper($stock->storage->name)), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Durée de stockage prévue : '.$stock->expired_at.' JOURS'), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Type de stockage : '.$stock->type_storage), 0, 1);
        self::$obj->cell(50, 5, utf8_decode('Zone dédiée : ________________________'), 0, 1);
        self::$obj->ln(5);

        self::$obj->invoiceTable(['#', 'DESIGNATION PRODUIT', 'CONTENANT', 'QUANTITE', 'ETAT GENERAL/OBSERVATIONS', 'COÛT'], $stock); 
        self::$obj->output();
        exit;
    }
}
