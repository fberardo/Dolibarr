<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2012      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014      Raphaël Doursenaud    <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014      Teddy Andreotti       <125155@supinfo.com>
 * Copyright (C) 2015      Juanjo Menent		 <jmenent@2byte.es>
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
 *	\file       htdocs/compta/paiement.php
 *	\ingroup    facture
 *	\brief      Payment page for customers invoices
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/customer_account/class/customeraccountmovement.class.php');
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/cheque.class.php';

$langs->load('companies');
$langs->load('bills');
$langs->load('banks');
$langs->load('multicurrency');
$langs->load('customeraccount@customer_account');

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm');

$facid		= GETPOST('facid','int');
$socname	= GETPOST('socname');
$accountid	= GETPOST('accountid');
$paymentnum	= GETPOST('num_paiement');

$sortfield	= GETPOST('sortfield','alpha');
$sortorder	= GETPOST('sortorder','alpha');
$page		= GETPOST('page','int');

$amounts=array();
$amountsresttopay=array();
$addwarning=0;

$multicurrency_amounts=array();
$multicurrency_amountsresttopay=array();

// Security check
$socid=0;
if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

$object=new Facture($db);

// Load object
if ($facid > 0)
{
	$ret=$object->fetch($facid);
}

// Initialize technical object to manage hooks of paiements. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('paiementcard','globalcard'));

/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($action == 'add_paiement' || ($action == 'confirm_paiement' && $confirm=='yes'))
    {
        $error = 0;

        $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
        $paiement_id = 0;
        $totalpayment = 0;
        $multicurrency_totalpayment = 0;
        $atleastonepaymentnotnull = 0;

        // Generate payment array and check if there is payment higher than invoice and payment date before invoice date
        $tmpinvoice=new Facture($db); 
        foreach ($_POST as $key => $value)
        {
            if (substr($key,0,7) == 'amount_')
            {
                $cursorfacid = substr($key,7);
                $amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
                $totalpayment = $totalpayment + $amounts[$cursorfacid];
                if (! empty($amounts[$cursorfacid])) $atleastonepaymentnotnull++;
                $result=$tmpinvoice->fetch($cursorfacid);
                if ($result <= 0) dol_print_error($db);
                $amountsresttopay[$cursorfacid]=price2num($tmpinvoice->total_ttc - $tmpinvoice->getSommePaiement());
                if ($amounts[$cursorfacid])
                {
                    // Check amount
                    if ($amounts[$cursorfacid] && (abs($amounts[$cursorfacid]) > abs($amountsresttopay[$cursorfacid])))
                    {
                        $addwarning=1;
                        $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
                    }
                    // Check date
                    if ($datepaye && ($datepaye < $tmpinvoice->date))
                    {
                        $langs->load("errors");
                        //$error++;
                        setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye,'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
                    }
                }

                $formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => $_POST[$key]);
            }
            elseif (substr($key,0,21) == 'multicurrency_amount_')
            {
                $cursorfacid = substr($key,21);
                $multicurrency_amounts[$cursorfacid] = price2num(trim(GETPOST($key)));
                $multicurrency_totalpayment += $multicurrency_amounts[$cursorfacid];
                if (! empty($multicurrency_amounts[$cursorfacid])) $atleastonepaymentnotnull++;
                $result=$tmpinvoice->fetch($cursorfacid);
                if ($result <= 0) dol_print_error($db);
                $multicurrency_amountsresttopay[$cursorfacid]=price2num($tmpinvoice->multicurrency_total_ttc - $tmpinvoice->getSommePaiement(1));
                if ($multicurrency_amounts[$cursorfacid])
                {
                    // Check amount
                    if ($multicurrency_amounts[$cursorfacid] && (abs($multicurrency_amounts[$cursorfacid]) > abs($multicurrency_amountsresttopay[$cursorfacid])))
                    {
                        $addwarning=1;
                        $formquestion['text'] = img_warning($langs->trans("PaymentHigherThanReminderToPay")).' '.$langs->trans("HelpPaymentHigherThanReminderToPay");
                    }
                    // Check date
                    if ($datepaye && ($datepaye < $tmpinvoice->date))
                    {
                        $langs->load("errors");
                        //$error++;
                        setEventMessages($langs->transnoentities("WarningPaymentDateLowerThanInvoiceDate", dol_print_date($datepaye,'day'), dol_print_date($tmpinvoice->date, 'day'), $tmpinvoice->ref), null, 'warnings');
                    }
                }

                $formquestion[$i++]=array('type' => 'hidden','name' => $key,  'value' => GETPOST($key, 'int'));
            }
        }

        // Check parameters
        if (! GETPOST('paiementcode'))
        {
            setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('PaymentMode')), null, 'errors');
            $error++;
        }

        if (! empty($conf->banque->enabled))
        {
            // If bank module is on, account is required to enter a payment
            if (GETPOST('accountid') <= 0)
            {
                setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')), null, 'errors');
                $error++;
            }
        }

        if (empty($totalpayment) && empty($multicurrency_totalpayment) && empty($atleastonepaymentnotnull))
        {
            setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->trans('PaymentAmount')), null, 'errors');
            $error++;
        }

        if (empty($datepaye))
        {
            setEventMessages($langs->transnoentities('ErrorFieldRequired',$langs->transnoentities('Date')), null, 'errors');
            $error++;
        }

        // Check if payments in both currency
        if ($totalpayment > 0 && $multicurrency_totalpayment > 0)
        {
            setEventMessages($langs->transnoentities('ErrorPaymentInBothCurrency'), null, 'errors');
            $error++;
        }

        if ($totalpayment > GETPOST('available')) {
            setEventMessages($langs->transnoentities('CustomerAccountPaiementSuperaSaldoTotalCuenta'), null, 'errors');
            $error++;
        }
    }

    /*
     * Action add_paiement
     */
    if ($action == 'add_paiement')
    {
        if ($error)
        {
            $action = 'create';
        }
        // Le reste propre a cette action s'affiche en bas de page.
    }

    /*
     * Action confirm_paiement
     */
    if ($action == 'confirm_paiement' && $confirm == 'yes')
    {
        $error=0;

        $datepaye = dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));

        $db->begin();

        // Clean parameters amount if payment is for a credit note
        if (GETPOST('type') == 2)
        {
            foreach ($amounts as $key => $value)	// How payment is dispatch
            {
                $newvalue = price2num($value,'MT');
                $amounts[$key] = -$newvalue;
            }

                foreach ($multicurrency_amounts as $key => $value)	// How payment is dispatch
            {
                $newvalue = price2num($value,'MT');
                $multicurrency_amounts[$key] = -$newvalue;
            }
        }

        if (! empty($conf->banque->enabled))
        {
            // Si module bank actif, un compte est obligatoire lors de la saisie d'un paiement
            if (GETPOST('accountid') <= 0)
            {
                    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('AccountToCredit')), null, 'errors');
                    $error++;
            }
        }

        // Creation of payment line
        $paiement = new Paiement($db);
        $paiement->datepaye     = $datepaye;
        $paiement->amounts      = $amounts;   // Array with all payments dispatching
        $paiement->multicurrency_amounts = $multicurrency_amounts;   // Array with all payments dispatching
        $paiement->paiementid   = dol_getIdFromCode($db,GETPOST('paiementcode'),'c_paiement');
        $paiement->note         = GETPOST('comment');

        if (! $error)
        {
            $paiement_id = $paiement->create($user, (GETPOST('closepaidinvoices')=='on'?1:0));
            if ($paiement_id < 0)
            {
                setEventMessages($paiement->error, $paiement->errors, 'errors');
                $error++;
            }
        }

        if (! $error)
        {
            $label='(CustomerInvoicePayment)';
            if (GETPOST('type') == 2) $label='(CustomerInvoicePaymentBack)';

            if (isset($_POST["fieldfk_cheque"]) && !empty($_POST["fieldfk_cheque"]))
            {
                foreach ($_POST["fieldfk_cheque"] as $value)
                {
                    $cheque = new cheque($db);
                    $result = $cheque->fetch($value);

                    if ($result >= 0)
                    {

                        $result2 = $paiement->addPaymentToBank($user, 'payment', $label, GETPOST('accountid'), $cheque->chqemetteur, $cheque->chqbank);
                        if ($result2 < 0)
                        {
                            setEventMessages($paiement->error, $paiement->errors, 'errors');
                            $error++;
                        }

                        $cheque->customer_used = $paiement_id;
                        $cheque->update($user);
                    }
                    else
                    {
                        setEventMessages($cheque->error, $cheque->errors, 'errors');
                        $error++;
                    }
                }
            }
        }

        if (! $error)
        {
            $sql = "SELECT";
            $sql.= " a.rowid";
            $sql.= " FROM ".MAIN_DB_PREFIX."customer_account as a";
            $sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON (a.fk_societe = s.rowid)";
            $sql.= " WHERE s.rowid = ".GETPOST(socid);

            $resql=$db->query($sql);
            if (! $resql)
            {
                dol_print_error($db);
                exit;
            }

            $obj = $db->fetch_object($resql);

            foreach($amounts as $key => $value) {
                if (is_numeric($value) && $value <> 0) {

                    $customerAccountMovement = new customeraccountmovement($db);
                    $customerAccountMovement->entity = 1;
                    $customerAccountMovement->fk_customer_account = $obj->rowid;
                    $customerAccountMovement->amount = -$value;
                    $customerAccountMovement->label = 'Pago de Factura ID['.$key.']';
                    $customerAccountMovement->dateo = $datepaye;
                    $customerAccountMovement->active = 1;
                    $customerAccountMovement->paiementid = 0; // dol_getIdFromCode($db,GETPOST('paiementcode'),'c_paiement');
                    //$customerAccountMovement->fk_cheque = NULL;

                    $result = $customerAccountMovement->create($user);

                    if ($result < 0)
                    {
                        setEventMessages($customerAccountMovement->error, $customerAccountMovement->errors, 'errors');
                        $error++;
                    }
                }
            }
        }

        if (! $error)
        {
            $db->commit();

            // If payment dispatching on more than one invoice, we keep on summary page, otherwise go on invoice card
            $invoiceid=0;
            foreach ($paiement->amounts as $key => $amount)
            {
                $facid = $key;
                if (is_numeric($amount) && $amount <> 0)
                {
                    if ($invoiceid != 0) $invoiceid=-1; // There is more than one invoice payed by this payment
                    else $invoiceid=$facid;
                }
            }
            if ($invoiceid > 0) $loc = DOL_URL_ROOT.'/compta/facture.php?facid='.$invoiceid;
            else $loc = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$paiement_id;
            header('Location: '.$loc);
            exit;
        }
        else
        {
            $db->rollback();
        }
    }
}


/*
 * View
 */

llxHeader();

$form=new Form($db);

if (isset($_POST["fieldfk_cheque"]) && !empty($_POST["fieldfk_cheque"]))
{
    foreach ($_POST["fieldfk_cheque"] as $value)
    {
        print "\n".'<input type="hidden" name="hiddencheckboxes" value="'.$value.'">';
    }
}


if ($action == 'create' || $action == 'confirm_paiement' || $action == 'add_paiement')
{
    $facture = new Facture($db);
    $result=$facture->fetch($facid);

    if ($result >= 0)
    {
        $facture->fetch_thirdparty();

        $title='';
        if ($facture->type != 2) $title.=$langs->trans("EnterPaymentReceivedFromCustomer");
        if ($facture->type == 2) $title.=$langs->trans("EnterPaymentDueToCustomer");
        print load_fiche_titre($title);

        // Initialize data for confirmation (this is used because data can be change during confirmation)
        if ($action == 'add_paiement')
        {
            $i=0;

            $formquestion[$i++]=array('type' => 'hidden','name' => 'facid', 'value' => $facture->id);
            $formquestion[$i++]=array('type' => 'hidden','name' => 'socid', 'value' => $facture->socid);
            $formquestion[$i++]=array('type' => 'hidden','name' => 'type',  'value' => $facture->type);
        }

        // Invoice with Paypal transaction
        // TODO add hook possibility (regis)
        if (! empty($conf->paypalplus->enabled) && $conf->global->PAYPAL_ENABLE_TRANSACTION_MANAGEMENT && ! empty($facture->ref_int))
        {
                if (! empty($conf->global->PAYPAL_BANK_ACCOUNT)) $accountid=$conf->global->PAYPAL_BANK_ACCOUNT;
                $paymentnum=$facture->ref_int;
        }

        $SIGN = ($facture->type == 2) ? '+' : '-';

        print "\n".'<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">';
        print "\n".'<script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>';

        print "\n".'<script type="text/javascript" language="javascript">';
        print '
//
// Updates "Select all" control in a data table
//
function updateDataTableSelectAllCtrl(table){
    var $table             = table.table().node();
    var $chkbox_all        = $(\'tbody input[type="checkbox"]\', $table);
    var $chkbox_checked    = $(\'tbody input[type="checkbox"]:checked\', $table);
    var chkbox_select_all  = $(\'thead input[name="select_all"]\', $table).get(0);

    // If none of the checkboxes are checked
    if ($chkbox_checked.length === 0) {
        chkbox_select_all.checked = false;
        if(\'indeterminate\' in chkbox_select_all) {
            chkbox_select_all.indeterminate = false;
        }

    // If all of the checkboxes are checked
    } else if ($chkbox_checked.length === $chkbox_all.length){
        chkbox_select_all.checked = true;
        if (\'indeterminate\' in chkbox_select_all) {
            chkbox_select_all.indeterminate = false;
        }

    // If some of the checkboxes are checked
    } else {
        chkbox_select_all.checked = true;
        if(\'indeterminate\' in chkbox_select_all) {
            chkbox_select_all.indeterminate = true;
        }
    }
}

$(document).ready(function () {
    // Array holding selected row IDs
    var rows_selected = [];
    var table = $(\'#tablacheques\').DataTable({
        \'columnDefs\': [{
        \'targets\': 0,
        \'searchable\': false,
        \'orderable\': false,
        \'width\': "1%",
        \'className\': \'dt-body-center\',
        \'render\': function (data, type, full, meta) {
                return \'<input type="checkbox" name="checkbox_cheques" value="\' + full[1] + \'" id="check_\' + full[1] + \'">\';
            }
        },
        {
            \'targets\': 1,
            \'visible\': false,
            \'searchable\': false
        }],
        \'order\': [[1, \'asc\']],
        \'ordering\': false,
        \'rowCallback\': function(row, data, dataIndex) {
            // Get row ID
            var rowId = data[1];

            // If row ID is in the list of selected row IDs
            if($.inArray(rowId, rows_selected) !== -1) {
                $(row).find(\'input[type="checkbox"]\').prop(\'checked\', true);
                $(row).addClass(\'selected\');
            }
        },
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
            \'info\': \'Mostrando página _PAGE_ de _PAGES_\',
            \'infoEmpty\': \'No hay cheques para mostrar\',
            \'infoFiltered\': \' - mostrando de _MAX_ registros\'
        },
        \'lengthMenu\': [[10, 25, 50, -1], [10, 25, 50, "All"]]
    });

    // Handle click on checkbox
    $(\'#tablacheques tbody\').on(\'click\', \'input[type="checkbox"]\', function(e){
        var $row = $(this).closest(\'tr\');

        // Get row data
        var data = table.row($row).data();

        // Get row ID
        var rowId = data[1];

        // Determine whether row ID is in the list of selected row IDs
        var index = $.inArray(rowId, rows_selected);

        // If checkbox is checked and row ID is not in list of selected row IDs
        if(this.checked && index === -1) {
           rows_selected.push(rowId);

        // Otherwise, if checkbox is not checked and row ID is in list of selected row IDs
        } else if (!this.checked && index !== -1) {
           rows_selected.splice(index, 1);
        }

        if(this.checked) {
           $row.addClass(\'selected\');
        } else {
           $row.removeClass(\'selected\');
        }

        // Update state of "Select all" control
        updateDataTableSelectAllCtrl(table);

        // Prevent click event from propagating to parent
        e.stopPropagation();
    });

    // Handle click on table cells with checkboxes
    $(\'#tablacheques\').on(\'click\', \'tbody td, thead th:first-child\', function(e){
        $(this).parent().find(\'input[type="checkbox"]\').trigger(\'click\');
    });

    // Handle click on "Select all" control
    $(\'thead input[name="select_all"]\', table.table().container()).on(\'click\', function(e){
        if(this.checked){
            $(\'#tablacheques tbody input[type="checkbox"]:not(:checked)\').trigger(\'click\');
        } else {
            $(\'#tablacheques tbody input[type="checkbox"]:checked\').trigger(\'click\');
        }

        // Prevent click event from propagating to parent
        e.stopPropagation();
    });

    // Handle table draw event
    table.on(\'draw\', function(){
        // Update state of "Select all" control
        updateDataTableSelectAllCtrl(table);
    });

    // Handle form submission event
    $(\'#payment_form\').on(\'submit\', function(e) {
        var form = this;

        // Iterate over all selected checkboxes
        $.each(rows_selected, function(index, rowId) {
            // Create a hidden element
            $(form).append(
                $(\'<input>\')
                    .attr(\'type\', \'hidden\')
                    .attr(\'name\', \'fieldfk_cheque[]\')
                    .val(rowId)
            );
        });
    });

    selectChequesInit();

    function selectChequesInit() {
        var selectedCheques = $(\'input[name="hiddencheckboxes"]\');
        $.each(selectedCheques, function(index, hiddenElement) {
            $(\'#check_\' + hiddenElement.value + \'\').chequed = true;
            $(\'#check_\' + hiddenElement.value + \'\').trigger(\'click\');
        });
    }
}); // end document.ready()    ';
        print '	</script>'."\n";

        print "\n".'<script type="text/javascript" language="javascript">';
        print '$(document).ready(function () {'."\n";
        print '         function resultAvailable() {

                            var lines = $("#payment_form").find("input.amount");
                            var result = $("input[name=available]").val();
                            lines.each(function(index) {
                                amount_value = $(this).val();
                                result = Number(result) '.$SIGN.' Number(amount_value);
                            });

                            return result;
                        }'."\n";

        print '         callForResult();'."\n";

        // Add realtime total information
        if ($conf->use_javascript_ajax)
        {
            print '	setPaiementCode();

                        $("#selectpaiementcode").change(function() {
                            setPaiementCode();
                        });

                        function setPaiementCode()
                        {
                            var code = $("#selectpaiementcode option:selected").val();

                            if (code == \'CHQ\' || code == \'PRE\')
                            {
                                $(\'input[type="search"]\').prop("disabled", false);
                                $(\'select[name="tablacheques_length"]\').prop("disabled", false);
                                $(\'input[type="checkbox"]\').prop("disabled", false);
                                $(\'.dataTables_paginate\').show();

                            }
                            else
                            {
                                $(\'input[type="search"]\').prop("disabled", true);
                                $(\'select[name="tablacheques_length"]\').prop("disabled", true);
                                $(\'input[type="checkbox"]\').prop("disabled", true);
                                $(\'.dataTables_paginate\').hide();
                            }
                        }

                        function _elemToJson(selector)
                        {
                            var subJson = {};
                            $.map(selector.serializeArray(), function(n,i)
                            {
                                subJson[n["name"]] = n["value"];
                            });

                            return subJson;
                        }

                        function callForResult(imgId)
                        {
                            var json = {};
                            var form = $("#payment_form");

                            json["invoice_type"] = $("#invoice_type").val();
                            json["amountPayment"] = $("#amountpayment").attr("value");
                            json["amounts"] = _elemToJson(form.find("input.amount"));
                            json["remains"] = _elemToJson(form.find("input.remain"));

                            if (imgId != null) 
                            {
                                json["imgClicked"] = imgId;
                            }

                            $.post("'.DOL_URL_ROOT.'/compta/ajaxpayment.php", json, function(data)
                            {
                                json = $.parseJSON(data);

                                form.data(json);

                                for (var key in json)
                                {
                                    if (key == "result")
                                    {
                                        if (json["makeRed"])
                                        {
                                            $("#"+key).addClass("error");
                                        }
                                        else
                                        {
                                            $("#"+key).removeClass("error");
                                        }
                                        json[key]=json["label"]+" "+json[key];
                                        $("#"+key).text(json[key]);
                                    } else {/*console.log(key);*/
                                        form.find("input[name*=\""+key+"\"]").each(function() {
                                            $(this).attr("value", json[key]);
                                        });
                                    }
                                }
                            });

                            result = resultAvailable();
                            if (result >= 0) {
                                $("#result_available").text("( " + Number(result).toFixed(2) + " )");
                                //$("#result_available").removeClass("error");
                                $("#result_available").css( "color", "");
                            } else {
                                $("#result_available").text("( NO CUBRE )");
                                //$("#result_available").addClass("error");
                                $("#result_available").css( "color", "red");
                            }
                        }

                        $("#payment_form").find("input.amount").change(function() {
                                callForResult();
                        });

                        $("#payment_form").find("input.amount").keyup(function() {
                                callForResult();
                        });

                        //Add js for AutoFill
                        $(".AutoFillAmout").on(\'click touchstart\', function(){
                                $("input[name="+$(this).data(\'rowname\')+"]").val($(this).data("value")).trigger("change");
                        });

                        //Add js for AutoFill All
                        $(".AutoFillAmoutAll").on(\'click touchstart\', function() {

                                var lines = $("#payment_form").find("input.amount");
                                var available = $("input[name=available]").val();
                                lines.each(function(index) {
                                    remain_name = $(this).attr("name").replace(/\amount_/g, "remain_");
                                    remain_value = $("input[name=" + remain_name + "]").val();

                                    console.log("quedan antes: " + available);
                                    //console.log("hay que restar: " + remain_value);
                                    console.log("hay que ('.$SIGN.'): " + remain_value);

                                    if (available '.$SIGN.' remain_value >= 0) {
                                        available = Number(available) '.$SIGN.' Number(remain_value);
                                        console.log("Cubre. Quedan ahora: " + Number(available).toFixed(2));

                                        $(this).val($("input[name=" + remain_name + "]").val());
                                    } else {
                                        if (available != 0) {
                                            console.log("No cubre. Quedan: " + Number(available).toFixed(2));

                                            // poner el remanente
                                            console.log("Pago parcial: " + Number(available).toFixed(2) + ". Quedan ahora: 0");

                                            $(this).val(Number(available).toFixed(2));
                                            available = 0;

                                            // Si comentamos esta línea va a intentar seguir buscando en los siguientes, hasta el final
                                            return false;
                                        }
                                    }
                                });

                                callForResult();
                        });

                        //jQuery extension method:
                        jQuery.fn.filterByText = function(textbox) {
                            return this.each(function() {
                                var select = this;
                                var options = [];
                                $(select).find("option").each(function() {
                                    options.push({
                                        value: $(this).val(),
                                        text: $(this).text(),
                                        datafilter: "" + $(this).data("filter") // $(this).attr("data-filter")
                                    });
                                });

                                $(select).data("options", options);

                                $(textbox).bind("change keyup", function() {
                                    var options = $(select).empty().data("options");
                                    var search = $.trim($(this).val());
                                    var regex = new RegExp(search, "gi");

                                    $.each(options, function(i) {
                                        var option = options[i];
                                        var hasAny = false;
                                        if (search.length == 0) {
                                            $(select).append(
                                                $("<option>").text(option.text).val(option.value)
                                            );
                                            $(select).val(0);
                                        } else {
                                            if (option.value == 0) {
                                                $(select).append(
                                                    $("<option>").text(option.text).val(option.value)
                                                );
                                            } else {
                                                if (option.datafilter.match(regex) !== null) {
                                                    $(select).append(
                                                        $("<option>").text(option.text).val(option.value)
                                                    );
                                                    if (!hasAny) {
                                                        $(select).val(option.value);
                                                    }
                                                    hasAny = true;
                                                }
                                            }
                                        }
                                    });

                                    $(select).trigger("change");
                                });
                            });
                        };

                        $(function() {
                            $("#selectcheque").filterByText($("#filterByChequeNumber"));
                        });';
        }

        print '	});'."\n";
        print '	</script>'."\n";

        print '<form id="payment_form" name="add_paiement" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="add_paiement">';
        print '<input type="hidden" name="facid" value="'.$facture->id.'">';
        print '<input type="hidden" name="socid" value="'.$facture->socid.'">';
        print '<input type="hidden" name="type" id="invoice_type" value="'.$facture->type.'">';
        print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($facture->thirdparty->name).'">';

        dol_fiche_head();

        print '<table class="border" width="100%">';

        // Third party
        print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans('Company').'</span></td><td>'.$facture->thirdparty->getNomUrl(4)."</td></tr>\n";

        // Date payment
        print '<tr><td><span class="fieldrequired">'.$langs->trans('Date').'</span></td><td>';
        $datepayment = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
        $datepayment= ($datepayment == '' ? (empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'') : $datepayment);
        $form->select_date($datepayment,'','','',0,"add_paiement",1,1,0,0,'','',$facture->date);
        print '</td></tr>';

        // Payment mode
        print '<tr><td><span class="fieldrequired">'.$langs->trans('PaymentMode').'</span></td><td>';
        $form->select_types_paiements((GETPOST('paiementcode')?GETPOST('paiementcode'):$facture->mode_reglement_code),'paiementcode','',2);
        print "</td>\n";
        print '</tr>';
        
        // Bank account
        print '<tr>';
        if (! empty($conf->banque->enabled))
        {
            if ($facture->type != 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToCredit').'</span></td>';
            if ($facture->type == 2) print '<td><span class="fieldrequired">'.$langs->trans('AccountToDebit').'</span></td>';
            print '<td>';
            $form->select_comptes($accountid,'accountid',0,'',2);
            print '</td>';
        }
        else
        {
            print '<td>&nbsp;</td>';
        }
        print "</tr>\n";
        print '</table>';
        
        // Cheques
        $rowcheques = '';
        $sql2 = "SELECT";
        $sql2.= " mvmt.fk_cheque";
        $sql2.= " FROM ".MAIN_DB_PREFIX."customer_account_movement mvmt";
        $sql2.= " INNER JOIN ".MAIN_DB_PREFIX."customer_account acc ON (mvmt.fk_customer_account = acc.rowid)";
        $sql2.= " INNER JOIN ".MAIN_DB_PREFIX."cheque chq ON (mvmt.fk_cheque = chq.rowid)";
        $sql2.= " WHERE acc.fk_societe = ".$facture->socid;
        $sql2.= " AND mvmt.fk_cheque IS NOT NULL";
        $sql2.= " AND chq.customer_used IS NULL";
        $sql2.= " AND chq.supplier_used IS NULL";
        
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
                    $cheque->fetch($chequeid->fk_cheque);
                    
                    $rowcheques .= '<tr>';
                    $rowcheques .= '<td></td>';
                    $rowcheques .= '<td>'.$cheque->id.'</td>';
                    $rowcheques .= '<td>'.$cheque->num_paiement.'</td>';
                    $rowcheques .= '<td>'.$cheque->chqemetteur.'</td>';
                    $rowcheques .= '<td>'.$cheque->chqbank.'</td>';
                    $rowcheques .= '<td>'.dol_print_date($cheque->datecheck, "day").'</td>';
                    $rowcheques .= '<td>'.price($cheque->amountcheck).'</td>';
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
                          <th><input name="select_all" value="1" type="checkbox"></th>
                          <th>Hidden ID</th>
                          <th>'.$langs->trans('Numero').'<em>('.$langs->trans("ChequeOrTransferNumber").')</em></th>
                          <th>'.$langs->trans("ChequeMaker").'</th>
                          <th>'.$langs->trans('Bank').'<em>('.$langs->trans("ChequeBank").')</em></th>
                          <th>Fecha</th>
                          <th>Monto</th>
                       </tr>
                    </thead>
                    <tbody>'.$rowcheques.'</tbody>
            </table>';

	dol_fiche_end();

	
        /*
         * List of unpaid invoices
         */
		
        $sql = 'SELECT f.rowid as facid, f.facnumber, f.total_ttc, f.multicurrency_code, f.multicurrency_total_ttc, f.type, ';
        $sql.= ' f.datef as df, f.fk_soc as socid';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		
        if (!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS))
        {
            $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON (f.fk_soc = s.rowid)';
        }

        $sql.= ' WHERE f.entity = '.$conf->entity;
        $sql.= ' AND (f.fk_soc = '.$facture->socid;
        
        if (!empty($conf->global->FACTURE_PAYMENTS_ON_DIFFERENT_THIRDPARTIES_BILLS) && !empty($facture->thirdparty->parent))
        {
            $sql.= ' OR f.fk_soc IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'societe WHERE parent = '.$facture->thirdparty->parent.')';
        }
		
        $sql.= ') AND f.paye = 0';
        $sql.= ' AND f.fk_statut = 1'; // Statut=0 => not validated, Statut=2 => canceled
        if ($facture->type != 2)
        {
            $sql .= ' AND type IN (0,1,3,5)';	// Standard invoice, replacement, deposit, situation
        }
        else
        {
            $sql .= ' AND type = 2';		// If paying back a credit note, we show all credit notes
        }
        //$sql .= ' AND type IN (0,1,2,3,5)';	// Standard invoice, replacement, deposit, situation. Discard 4. Proforma invoice (should not be used. a proforma is an order)

        // Sort invoices by date and serial number: the older one comes first
        $sql.=' ORDER BY f.datef ASC, f.facnumber ASC';

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            if ($num > 0)
            {
            	$sign=1;
            	if ($facture->type == 2) $sign=-1;

                $arraytitle=$langs->trans('Invoice');
                if ($facture->type == 2) $arraytitle=$langs->trans("CreditNotes");
                $alreadypayedlabel=$langs->trans('Received');
                $multicurrencyalreadypayedlabel=$langs->trans('MulticurrencyReceived');
                if ($facture->type == 2) { $alreadypayedlabel=$langs->trans("PaidBack"); $multicurrencyalreadypayedlabel=$langs->trans("MulticurrencyPaidBack"); }
                $remaindertopay=$langs->trans('RemainderToTake');
                $multicurrencyremaindertopay=$langs->trans('MulticurrencyRemainderToTake');
                if ($facture->type == 2) { $remaindertopay=$langs->trans("RemainderToPayBack"); $multicurrencyremaindertopay=$langs->trans("MulticurrencyRemainderToPayBack"); }

                $i = 0;
                //print '<tr><td colspan="3">';
                print '<br>';
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td>'.$arraytitle.'</td>';
                print '<td align="center">'.$langs->trans('Date').'</td>';
                if (!empty($conf->multicurrency->enabled)) print '<td>'.$langs->trans('Currency').'</td>';
                if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('MulticurrencyAmountTTC').'</td>';
                if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$multicurrencyalreadypayedlabel.'</td>';
                if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$multicurrencyremaindertopay.'</td>';
                print '<td align="right">'.$langs->trans('AmountTTC').'</td>';
                print '<td align="right">'.$alreadypayedlabel.'</td>';
                print '<td align="right">'.$remaindertopay.'</td>';
                print '<td align="right">'.$langs->trans('PaymentAmount').'</td>';
                if (!empty($conf->multicurrency->enabled)) print '<td align="right">'.$langs->trans('MulticurrencyPaymentAmount').'</td>';
                print '<td align="right">&nbsp;</td>';
                print "</tr>\n";

                $var=true;
                $total=0;
                $totalrecu=0;
                $totalrecucreditnote=0;
                $totalrecudeposits=0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);
                    $var=!$var;

		    $soc = new Societe($db);
		    $soc->fetch($objp->socid);

                    $invoice=new Facture($db);
                    $invoice->fetch($objp->facid);
                    $paiement = $invoice->getSommePaiement();
                    $creditnotes=$invoice->getSumCreditNotesUsed();
                    $deposits=$invoice->getSumDepositsUsed();
                    $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
                    $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
					
                    // Multicurrency Price
                    if (!empty($conf->multicurrency->enabled)) 
                    {
                        $multicurrency_payment = $invoice->getSommePaiement(1);
			$multicurrency_creditnotes=$invoice->getSumCreditNotesUsed(1);
			$multicurrency_deposits=$invoice->getSumDepositsUsed(1);
			$multicurrency_alreadypayed=price2num($multicurrency_payment + $multicurrency_creditnotes + $multicurrency_deposits,'MT');
	                $multicurrency_remaintopay=price2num($invoice->multicurrency_total_ttc - $multicurrency_payment - $multicurrency_creditnotes - $multicurrency_deposits,'MT');
                    }
					
                    print '<tr '.$bc[$var].'>';

                    print '<td>';
                    print $invoice->getNomUrl(1,'');
                    if($objp->socid != $facture->thirdparty->id) print ' - '.$soc->getNomUrl(1).' ';
                    print "</td>\n";

                    // Date
                    print '<td align="center">'.dol_print_date($db->jdate($objp->df),'day')."</td>\n";
                    
                    // Currency
                    if (!empty($conf->multicurrency->enabled)) print '<td align="center">'.$objp->multicurrency_code."</td>\n";
                    
                        // Multicurrency Price
                        if (!empty($conf->multicurrency->enabled)) 
                        {
                            print '<td align="right">';
                            if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $objp->multicurrency_total_ttc);
                            print '</td>';

                            // Multicurrency Price
                            print '<td align="right">';
                            if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) 
                            {
                                print price($sign * $multicurrency_payment);
                                if ($multicurrency_creditnotes) print '+'.price($multicurrency_creditnotes);
                                if ($multicurrency_deposits) print '+'.price($multicurrency_deposits);
                            }
                            print '</td>';
					
    			// Multicurrency Price
    			print '<td align="right">';
    			if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency) print price($sign * $multicurrency_remaintopay);
    			print '</td>';
                    }
					
                    // Price
                    print '<td align="right">'.price($sign * $objp->total_ttc).'</td>';
					
                    // Received or paid back
                    print '<td align="right">'.price($sign * $paiement);
                    if ($creditnotes) print '+'.price($creditnotes);
                    if ($deposits) print '+'.price($deposits);
                    print '</td>';

                    // Remain to take or to pay back
                    print '<td align="right">'.price($sign * $remaintopay).'</td>';
                    //$test= price(price2num($objp->total_ttc - $paiement - $creditnotes - $deposits));

                    // Amount
                    print '<td align="right">';

                    // Add remind amount
                    $namef = 'amount_'.$objp->facid;
                    $nameRemain = 'remain_'.$objp->facid;

                    if ($action != 'add_paiement')
                    {
                        if (!empty($conf->use_javascript_ajax))
							print img_picto("Auto fill",'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $remaintopay)."'");
                        print '<input type=hidden class="remain" name="'.$nameRemain.'" value="'.($sign * $remaintopay).'">';
                        print '<input type="text" size="8" class="amount" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                    else
                    {
                        print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.dol_escape_htmltag(GETPOST($namef)).'" disabled>';
                        print '<input type="hidden" name="'.$namef.'" value="'.dol_escape_htmltag(GETPOST($namef)).'">';
                    }
                    print "</td>";

                    // Multicurrency Price
                    if (! empty($conf->multicurrency->enabled)) 
                    {
                        print '<td align="right">';

                        // Add remind multicurrency amount
                        $namef = 'multicurrency_amount_'.$objp->facid;
                        $nameRemain = 'multicurrency_remain_'.$objp->facid;

                        if ($objp->multicurrency_code && $objp->multicurrency_code != $conf->currency)
                        {
                        if ($action != 'add_paiement')
                        {
                            if (!empty($conf->use_javascript_ajax))
                                                            print img_picto("Auto fill",'rightarrow', "class='AutoFillAmout' data-rowname='".$namef."' data-value='".($sign * $multicurrency_remaintopay)."'");
                            print '<input type=hidden class="multicurrency_remain" name="'.$nameRemain.'" value="'.$multicurrency_remaintopay.'">';
                            print '<input type="text" size="8" class="multicurrency_amount" name="'.$namef.'" value="'.$_POST[$namef].'">';
                        }
                        else
                        {
                            print '<input type="text" size="8" name="'.$namef.'_disabled" value="'.$_POST[$namef].'" disabled>';
                            print '<input type="hidden" name="'.$namef.'" value="'.$_POST[$namef].'">';
                        }
                        }
                        print "</td>";
                    }

                    // Warning
                    print '<td align="center" width="16">'; 
                    //print "xx".$amounts[$invoice->id]."-".$amountsresttopay[$invoice->id]."<br>";
                    if ($amounts[$invoice->id] && (abs($amounts[$invoice->id]) > abs($amountsresttopay[$invoice->id]))
                    	|| $multicurrency_amounts[$invoice->id] && (abs($multicurrency_amounts[$invoice->id]) > abs($multicurrency_amountsresttopay[$invoice->id])))
                    {
                        print ' '.img_warning($langs->trans("PaymentHigherThanReminderToPay"));
                    }
                    print '</td>';

                    $parameters=array();
                    $reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$objp,$action); // Note that $action and $object may have been modified by hook

                    print "</tr>\n";

                    $total+=$objp->total;
                    $total_ttc+=$objp->total_ttc;
                    $totalrecu+=$paiement;
                    $totalrecucreditnote+=$creditnotes;
                    $totalrecudeposits+=$deposits;
                    $i++;
                }
                $resta = $total_ttc - $totalrecu - $totalrecucreditnote - $totalrecudeposits;
                if ($i > 1)
                {
                    // Print total
                    print '<tr class="liste_total">';
                    print '<td colspan="3" align="left">'.$langs->trans('TotalTTC').'</td>';
                    if (!empty($conf->multicurrency->enabled)) print '<td></td>';
                    if (!empty($conf->multicurrency->enabled)) print '<td></td>';
                    if (!empty($conf->multicurrency->enabled)) print '<td></td>';
                    print '<td align="right"><b>'.price($sign * $total_ttc).'</b></td>';
                    print '<td align="right"><b>'.price($sign * $totalrecu);
                    if ($totalrecucreditnote) print '+'.price($totalrecucreditnote);
                    if ($totalrecudeposits) print '+'.price($totalrecudeposits);
                    print '</b></td>';
                    print '<td align="right"><b>'.price($sign * price2num($resta,'MT')).'</b></td>';
                    print '<td align="right" id="result" style="font-weight: bold;"></td>';
					if (!empty($conf->multicurrency->enabled)) print '<td align="right" id="multicurrency_result" style="font-weight: bold;"></td>';
                    print '<td align="center">&nbsp;</td>';
                    print "</tr>\n";
                }
            }
            
            $sql = "SELECT SUM(amount) FROM " . MAIN_DB_PREFIX . "customer_account_movement as m";
            $sql .= " , " . MAIN_DB_PREFIX . "customer_account as c";
            $sql .= " WHERE c.rowid = m.fk_customer_account";
            $sql .= " AND c.fk_societe = ".$facture->socid;

            $available = 0;
            $result = $db->query($sql);
            if ($result) {
                $row = $db->fetch_row($result);
                $available = ($row[0] != null) ? $row[0] : 0;

                $db->free($result);
            }
            print '<input type="hidden" name="available" value="'.$available.'">';

            // Disponible en la cuenta
            print '<tr height="30px">';
            print '<td colspan="3"></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            print '<td colspan="4"></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";

            print '<tr class="liste_total">';
            print '<td colspan="3">&nbsp;</td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            if (!empty($conf->multicurrency->enabled)) print '<td></td>';
            print '<td align="right">'.$langs->trans('CustomerAccountDisponible').'</td>';
            print '<td align="right"><b>'.price($available).'</b></td>';

            print '<td align="right">';
            //if ($available >= $resta) {
                if ($action != 'add_paiement') {
                    if (!empty($conf->use_javascript_ajax)) {
                        print img_picto("Auto fill All", 'rightarrow', "class='AutoFillAmoutAll'");
                    }
                }
            //}
            print '</td>';

            //print '<td align="right" id="result_available"><b>( '.price($available).' )</b></td>';
            // Esto lo hace la función callForResult()
            print '<td align="right" id="result_available"></td>';

            if (!empty($conf->multicurrency->enabled)) print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";

            print "</table>";
            //print "</td></tr>\n";
            
            $db->free($resql);
        }
        else
		{
            dol_print_error($db);
        }


        // Bouton Enregistrer
        if ($action != 'add_paiement')
        {
            $checkboxlabel=$langs->trans("ClosePaidInvoicesAutomatically");
            if ($facture->type == 2) $checkboxlabel=$langs->trans("ClosePaidCreditNotesAutomatically");
            $buttontitle=$langs->trans('ToMakePayment');
            if ($facture->type == 2) $buttontitle=$langs->trans('ToMakePaymentBack');

            print '<br><div class="center">';
            print '<input type="checkbox" checked name="closepaidinvoices"> '.$checkboxlabel;
            /*if (! empty($conf->prelevement->enabled))
            {
                $langs->load("withdrawals");
                if (! empty($conf->global->WITHDRAW_DISABLE_AUTOCREATE_ONPAYMENTS)) print '<br>'.$langs->trans("IfInvoiceNeedOnWithdrawPaymentWontBeClosed");
            }*/
            print '<br><input type="submit" class="button" value="'.dol_escape_htmltag($buttontitle).'"><br><br>';
            print '</div>';
        }

        // Form to confirm payment
        if ($action == 'add_paiement')
        {
            $preselectedchoice=$addwarning?'no':'yes';

            print '<br>';
            if (!empty($totalpayment)) $text=$langs->trans('ConfirmCustomerPayment',$totalpayment,$langs->trans("Currency".$conf->currency));
            if (!empty($multicurrency_totalpayment)) 
            {
		$text.='<br />'.$langs->trans('ConfirmCustomerPayment',$multicurrency_totalpayment,$langs->trans("paymentInInvoiceCurrency"));
            }
            if (GETPOST('closepaidinvoices'))
            {
                $text.='<br>'.$langs->trans("AllCompletelyPayedInvoiceWillBeClosed");
                print '<input type="hidden" name="closepaidinvoices" value="'.GETPOST('closepaidinvoices').'">';
            }
            print $form->formconfirm($_SERVER['PHP_SELF'].'?facid='.$facture->id.'&socid='.$facture->socid.'&type='.$facture->type,$langs->trans('ReceivedCustomersPayments'),$text,'confirm_paiement',$formquestion,$preselectedchoice);
        }

        print "</form>\n";
    }
}


/**
 *  Show list of payments
 */
if (! GETPOST('action'))
{
    if ($page == -1) $page = 0 ;
    $limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
    $offset = $limit * $page ;

    if (! $sortorder) $sortorder='DESC';
    if (! $sortfield) $sortfield='p.datep';

    $sql = 'SELECT p.datep as dp, p.amount, f.amount as fa_amount, f.facnumber';
    $sql.=', f.rowid as facid, c.libelle as paiement_type, p.num_paiement';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement as p, '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'c_paiement as c';
    $sql.= ' WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id';
    $sql.= ' AND f.entity = '.$conf->entity;
    if ($socid)
    {
        $sql.= ' AND f.fk_soc = '.$socid;
    }

    $sql.= ' ORDER BY '.$sortfield.' '.$sortorder;
    $sql.= $db->plimit($limit+1, $offset);
    $resql = $db->query($sql);

    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=true;

        print_barre_liste($langs->trans('Payments'), $page, $_SERVER["PHP_SELF"],'',$sortfield,$sortorder,'',$num);
        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('Invoice'),$_SERVER["PHP_SELF"],'facnumber','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'dp','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Type'),$_SERVER["PHP_SELF"],'libelle','','','',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans('Amount'),$_SERVER["PHP_SELF"],'fa_amount','','','align="right"',$sortfield,$sortorder);
		print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
        print "</tr>\n";

        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.$objp->facnumber."</a></td>\n";
            print '<td>'.dol_print_date($db->jdate($objp->dp))."</td>\n";
            print '<td>'.$objp->paiement_type.' '.$objp->num_paiement."</td>\n";
            print '<td align="right">'.price($objp->amount).'</td><td>&nbsp;</td>';

			$parameters=array();
			$reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$objp,$action); // Note that $action and $object may have been modified by hook

            print '</tr>';
            $i++;
        }
        print '</table>';
    }
}

llxFooter();

$db->close();
