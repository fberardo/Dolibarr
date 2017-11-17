<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014		Teddy Andreotti			<125155@supinfo.com>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Juanjo Menent			<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/fourn/facture/paiement.php
 *	\ingroup    fournisseur,facture
 *	\brief      Payment page for suppliers invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/cheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');
$langs->load('compta');
$langs->load('customeraccount@customer_account');

$action     = GETPOST('action','alpha');
$confirm	= GETPOST('confirm');

$facid=GETPOST('facid','int');
$socid=GETPOST('socid','int');
$accountid	= GETPOST('accountid');

$search_ref=GETPOST("search_ref","int");
$search_account=GETPOST("search_account","int");
$search_paymenttype=GETPOST("search_paymenttype");
$search_amount=GETPOST("search_amount",'alpha');    // alpha because we must be able to search on "< x"
$search_company=GETPOST("search_company",'alpha');
$search_payment_num=GETPOST('search_payment_num','alpha');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
$optioncss = GETPOST('optioncss','alpha');

$amounts = array();array();
$amountsresttopay=array();
$addwarning=0;

$multicurrency_amounts=array();
$multicurrency_amountsresttopay=array();

// Security check
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paymentsupplier'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('paymentsupplier');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$arrayfields=array();

/*
 * View
 */

//llxHeader('',$langs->trans('ListPayment'));
llxHeader('','Listado de Cheques');

$form=new Form($db);

print "\n".'<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">';
print "\n".'<script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>';

print "\n".'<script type="text/javascript" language="javascript">';

print '
$(document).ready(function () {
    // Array holding selected row IDs
    var table = $(\'#tablacheques\').DataTable({
        \'columnDefs\': [{
            \'targets\': 0,
            \'width\': "20%"
        },
        {
            \'targets\': 1,
            \'width\': "20%"
        },
        {
            \'targets\': 2,
            \'width\': "20%"
        },
        {
            \'targets\': 3,
            \'searchable\': false,
            \'orderable\': false
        },
        {
            \'targets\': 4,
            \'searchable\': false,
            \'orderable\': false
        },
        {
            \'targets\': 5,
            \'orderable\': false,
            \'width\': "20%"
        },
        {
            \'targets\': 6,
            \'orderable\': false,
            \'width\': "20%"
        }],
        \'order\': [[0, \'asc\']],
        \'ordering\': true,
        \'language\': {
            \'search\': \'Buscar\',
            \'paginate\': {
                \'first\':    \'Primero\',
                \'previous\': \'Anterior\',
                \'next\':     \'Siguiente\',
                \'last\':     \'Último\'
            },
            \'aria\': {
                \'paginate\': {
                    \'first\':    \'Primero\',
                    \'previous\': \'Anterior\',
                    \'next\':     \'Siguiente\',
                    \'last\':     \'Último\'
                }
            },
            \'lengthMenu\': \'Mostrar _MENU_ registros por página\',
            \'zeroRecords\': \'No hay cheques para mostrar\',
            \'info\': \'Mostrando elemento _START_ a _END_ (página _PAGE_ de _PAGES_) de _TOTAL_ registros\',
            \'infoEmpty\': \'No hay cheques para mostrar\',
            \'infoFiltered\': \' - Total: _MAX_ registros\'
        },
        \'lengthMenu\': [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });
}); // end document.ready()';

print '	</script>'."\n";                    

//print load_fiche_titre($langs->trans('DoPayment'));
print load_fiche_titre('Búsqueda de Cheques');

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

dol_fiche_head(null);

// Cheques
$rowcheques = '';
$sql2 = "SELECT";
$sql2.= " chq.rowid";
$sql2.= " FROM ".MAIN_DB_PREFIX."cheque chq";

$result2 = $db->query($sql2);
if ($result2)
{
    $num = $db->num_rows($result2);
    if ($num > 0) 
    {    
        $i=0;
        while ($i < $num) {
            $chequeid = $db->fetch_object($result2);
            $cheque = new cheque($db);
            $cheque->fetch($chequeid->rowid);
            
            $customer = 'No';
            if (isset($cheque->customer_used) && !empty($cheque->customer_used))
            {
                $customer = 'Sí';
                $paymentstatic = new Paiement($db);
                $paymentstatic->fetch($cheque->customer_used);
                
                $customer .= ' (' . $paymentstatic->getNomUrl(0) . ' )';
            }
            
            $supplier = 'No';
            if (isset($cheque->supplier_used) && !empty($cheque->supplier_used))
            {
                $supplier = 'Sí';
                $paiementfourn = new PaiementFourn($db);
                $paiementfourn->fetch($cheque->supplier_used);
                
                $supplier .= ' (' . $paiementfourn->getNomUrl(0) . ' )';
            }
            
            $rowcheques .= '<tr>';
            $rowcheques .= '<td>'.$cheque->num_paiement.'</td>';
            $rowcheques .= '<td>'.$cheque->chqemetteur.'</td>';
            $rowcheques .= '<td>'.$cheque->chqbank.'</td>';
            $rowcheques .= '<td>'.dol_print_date($cheque->datecheck, "day").'</td>';
            $rowcheques .= '<td>'.price($cheque->amountcheck).'</td>';
            $rowcheques .= '<td>'.$customer.'</td>';
            $rowcheques .= '<td>'.$supplier.'</td>';
            $rowcheques .= '</tr>';

            $i++;
        }
    }
}
$db->free($result2);

print '
    <table id="tablacheques" class="display select" cellspacing="0" width="100%">
            <thead>
               <tr>
                  <th>'.$langs->trans('Numero').'</th>
                  <th>'.$langs->trans('CheckTransmitter').'</th>
                  <th>'.$langs->trans('Bank').'</th>
                  <th>'.$langs->trans('Date').'</th>
                  <th>'.$langs->trans('CustomerAccountFieldamount').'</th>
                  <th>'.$langs->trans('ChequeWithCustomerPaiement').'</th>
                  <th>'.$langs->trans('ChequeWithSupplierPaiement').'</th>
               </tr>
            </thead>
            <tbody>'.$rowcheques.'</tbody>
    </table>';

dol_fiche_end();

print '</form>';

llxFooter();
$db->close();
