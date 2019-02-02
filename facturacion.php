<?php

use WHMCS\ClientArea;
use WHMCS\Module\Addon\Facturacom\Admin\CoreModule;

define('CLIENTAREA', true);

require __DIR__ . '/init.php';

$ca = new ClientArea();

$ca->setPageTitle('Factura tus servicios');
$ca->initPage();
$ca->requireLogin();

// Check login status
if ($ca->isLoggedIn()) {

    $CoreModule = new CoreModule;

    //variables de configuración
    $Settings = $CoreModule->getGonfiguration();
    $urlCallApi = $CoreModule->getURL($Settings);
    $systemURL = $CoreModule->getSystemURL();

    //Todas las orders del usuario
    $OrdersWHMCS = $CoreModule->getWhmcsInvoices($ca->getUserID());
    $clientInvoices = [];
    $clientOrders = [];

    foreach($OrdersWHMCS as $key => $value) {
        $clientOrders[] = $key;
    }

    //traemos todas las facturas. Si deseas cotejar pedidos elimina null e incorpora $clientOrders
    $Invoices = $CoreModule->getInvoicesFacturacom($ca->getUserID(), null);

    //Todas las orders de wh
    $InvoicesWHMCS = $CoreModule->getWhmcsInvoicesAll($ca->getUserID());

    //// START Código HM
    // Para prevenir que facturen si no se cobro IVA
    // Generar array asociativo de invoicesid y taxrates
    $hmInvocesTax = array_map(function($hmInvoice){
        // Preparar la API call local
        $hmPostData = array(
            // Buscar invoice
            'invoiceid' => $hmInvoice['orderId'],
        );
        // Ejecutar y regresar tasa de IVA como valor asociativo del orderid en el array
        $hmResults = localAPI('GetInvoice', $hmPostData);
        return $hmResults['taxrate'];
    }, $InvoicesWHMCS);
    // Exportar el array a smarty
    $smarty->assign('hminvoicetaxsmarty', $hmInvocesTax);
    //// END Código HM

    //object to array
    foreach ($Invoices['data'] as $key => $value) {
        if(array_key_exists($value['NumOrder'], $InvoicesWHMCS)){
            $clientInvoices[$key] = (array) $value;
        }
    }

    //Traemos los usos de CFDI
    $UsosCFDI = $CoreModule->getUsoCFDI();

    $ca->caching = false;
    $ca->assign('clientW', $ca->getUserID());
    $ca->assign('whmcsInvoices', $OrdersWHMCS);
    $ca->assign('clientInvoices', $clientInvoices);
    $ca->assign('systemURL', $systemURL);
    $ca->assign('apiUrl', $urlCallApi);
    $ca->assign('serieInvoices', $Settings['Serie']);
    $ca->assign('UsoCFDI', $Settings['UsoCFDI']);
    $ca->assign('Usos', $UsosCFDI);


} else {

    // User is not logged in
    echo "No tienes permisos para esta sección";

}

# Define the template filename to be used without the .tpl extension
$ca->setTemplate('customer_area/clientfacturacion');
$ca->output();
