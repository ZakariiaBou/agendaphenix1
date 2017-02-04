<?php
  /**************************************************************************\
  * Phenix Agenda                                                            *
  * http://phenix.gapi.fr                                                    *
  * Written by    Stephane TEIL            <phenix-agenda@laposte.net>       *
  * Contributors  Christian AUDEON (Omega) <christian.audeon@gmail.com>      *
  *               Maxime CORMAU (MaxWho17) <maxwho17@free.fr>                *
  *               Mathieu RUE (Frognico)   <matt_rue@yahoo.fr>               *
  *               Bernard CHAIX (Berni69)  <ber123456@free.fr>               *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  require("inc/nocache.inc.php");
  include("inc/param.inc.php");
  include("inc/html.inc.php");
  include("lang/$APPLI_LANGUE.php");
  include("inc/fonctions.inc.php");
  include('inc/class.mailer.php');
  if (isset($_GET['sid'])) {
    $idUser = Session_ok($sid);
    $refreshPage = "<META http-equiv=\"REFRESH\" content=\"60; url=info_surveille.php?sid=".$sid."\">\n  ";
  } else {
    // Version "standalone" du fichier qui permet de l'appeler via la crontab Linux ou des sites tels que Webcron ou CronJobs
    $idUser = 0;
    $APPLI_STYLE = "Petrole";
    $refreshPage = "";
  }

  // Initialisations
  define('RAPPEL_NOTE', 1);
  define('RAPPEL_ANNIV', 2);
  define('RAPPEL_ANNIV_CONTACT', 3);
  $nbRappel  = $nbMail = 0;
  $classMailerLoaded = $classSMTPLoaded = false;

  // Creation d'une nouvelle instance pour l'execution de requetes en boucle
  $DB = new Db($DB_CX->ConnexionID);

  function gereRappel($typeRappel) {
    global $nbRappel, $nbMail, $PREFIX_TABLE, $DB_CX, $DB;

    $noteID = 0;
    $sujetMail = $corpsMail = "";
    $destMail = array();
    // Requete a executer en fonction du type de rappel a notifier
    if ($typeRappel==RAPPEL_NOTE) {
      $sql=("SELECT age_id AS id, age_util_id AS idEmetteur, DATE_FORMAT(age_date,'%d/%m/%Y') AS dateEvent, age_heure_debut, age_libelle, age_detail, age_lieu, age_rappel, age_rappel_coeff, age_email AS envoiMail, aco_util_id AS idDestinataire, dest.util_email AS destEmail, CONCAT(exp.util_prenom,' ',exp.util_nom) AS expNom, exp.util_email AS expEmail, age_email_contact, cal_email, cal_emailpro, CONCAT(cal_prenom,' ',cal_nom) AS nomContact, tzn_gmt, tzn_date_ete, tzn_heure_ete, tzn_date_hiver, tzn_heure_hiver FROM ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur dest, ${PREFIX_TABLE}utilisateur exp, ${PREFIX_TABLE}timezone WHERE aco_rappel_ok=0 AND aco_termine=0 AND age_id=aco_age_id AND (age_aty_id=2 OR age_aty_id=3) AND age_rappel>0 AND TO_DAYS(age_date)-TO_DAYS('".gmdate("Y-m-d H:i:s", time())."') < 60 AND dest.util_id=aco_util_id AND exp.util_id=age_util_id AND tzn_zone=dest.util_timezone ORDER BY age_date, age_heure_debut");
    } elseif ($typeRappel==RAPPEL_ANNIV) {
      $sql=("SELECT age_id AS id, age_util_id AS idEmetteur, DATE_FORMAT(age_date,'%d/%m/".date("Y")."') AS dateEvent, age_libelle AS nomAnniv, age_date AS dateNaissance, aco_util_id AS idDestinataire, util_email AS destEmail, CONCAT(util_prenom,' ',util_nom) AS expNom, util_email AS expEmail, util_rappel_anniv, util_rappel_anniv_coeff, util_rappel_anniv_email AS envoiMail FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur WHERE aco_rappel_ok<".date("Y")." AND age_id=aco_age_id AND age_aty_id=1 AND util_id=aco_util_id AND util_rappel_anniv>0 AND TO_DAYS(DATE_FORMAT(age_date,'".date("Y")."-%m-%d 00:00:00'))-TO_DAYS('".date("Y-m-d H:i:s", time())."') < 60 ORDER BY dateEvent DESC");
    } elseif ($typeRappel==RAPPEL_ANNIV_CONTACT) {
      $sql=("SELECT cal_id AS id, cal_util_id AS idEmetteur, DATE_FORMAT(cal_date_naissance,'%d/%m/".date("Y")."') AS dateEvent, CONCAT(cal_prenom,' ',cal_nom) AS nomAnniv, cal_date_naissance AS dateNaissance, cal_util_id AS idDestinataire, util_email AS destEmail, CONCAT(util_prenom,' ',util_nom) AS expNom, util_email AS expEmail, util_rappel_anniv, util_rappel_anniv_coeff, util_rappel_anniv_email AS envoiMail FROM ${PREFIX_TABLE}calepin, ${PREFIX_TABLE}utilisateur WHERE cal_rappel_ok<".date("Y")." AND util_id=cal_util_id AND util_rappel_anniv>0 AND TO_DAYS(DATE_FORMAT(cal_date_naissance,'".date("Y")."-%m-%d 00:00:00'))-TO_DAYS('".date("Y-m-d H:i:s", time())."') < 60 ORDER BY dateEvent DESC");
    }
    $DB_CX->DbQuery($sql);
    while ($enr = $DB_CX->DbNextRow()) {
      $tabDate = explode("/",$enr['dateEvent']);
      // Recuperation des infos de timezone de l'utilisateur
      if (!empty($enr['tzn_gmt'])) {
        $tzGmt = $enr['tzn_gmt'];
        $tzEte = calculBasculeDST($enr['tzn_date_ete'],gmdate("Y"),$enr['tzn_heure_ete'],$tzGmt,0);
        $tzHiver = calculBasculeDST($enr['tzn_date_hiver'],gmdate("Y"),$enr['tzn_heure_hiver'],$tzGmt,1);
        $decalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(gmdate("H"),gmdate("i"),0,gmdate("n"),gmdate("j"),gmdate("Y")));
      }
      if ($typeRappel==RAPPEL_NOTE) {
        $tsEvent  = mktime($enr['age_heure_debut']+floor($decalageHoraire),(($enr['age_heure_debut']+$decalageHoraire)*60)%60,0,$tabDate[1],$tabDate[0],$tabDate[2]);
        $tsNow    = mktime(gmdate("H")+floor($decalageHoraire),gmdate("i")+($decalageHoraire*60)%60+($enr['age_rappel']*$enr['age_rappel_coeff']),0,gmdate("n"),gmdate("j"),gmdate("Y"));
        $libEvent = $enr['age_libelle'];
      } else {
        $tsEvent  = mktime(0,0,0,$tabDate[1],$tabDate[0],$tabDate[2]);
        $tsNow    = mktime(date("H"),date("i")+($enr['util_rappel_anniv']*$enr['util_rappel_anniv_coeff']),0,date("n"),date("j"),date("Y"));
        $libEvent = sprintf(trad("INFO_ANNIVERSAIRE_DE"),prefixeMot(strtolower(substr($enr['nomAnniv'],0,1)),trad("COMMUN_PREFIXE_D"),trad("COMMUN_PREFIXE_DE")).$enr['nomAnniv']);
      }
      if ($tsEvent<=$tsNow) {
        $nbRappel++;
        $DB->DbQuery("INSERT INTO ${PREFIX_TABLE}information (info_emetteur_id, info_destinataire_id, info_age_id, info_date, info_commentaire, info_heure_rappel) VALUES (".$enr['idEmetteur'].",".$enr['idDestinataire'].",".(($typeRappel!=RAPPEL_ANNIV_CONTACT) ? $enr['id'] : -1).",'".date("Y-m-d H:i",$tsEvent)."', '".$tsEvent."@".addslashes($libEvent)."', ".gmmktime().")");
        if ($typeRappel==RAPPEL_NOTE) {
          $DB->DbQuery("UPDATE ${PREFIX_TABLE}agenda_concerne SET aco_rappel_ok=1 WHERE aco_age_id=".$enr['id']." AND aco_util_id=".$enr['idDestinataire']);
        } elseif ($typeRappel==RAPPEL_ANNIV) {
          $DB->DbQuery("UPDATE ${PREFIX_TABLE}agenda_concerne SET aco_rappel_ok=".date("Y")." WHERE aco_age_id=".$enr['id']." AND aco_util_id=".$enr['idDestinataire']);
        } elseif ($typeRappel==RAPPEL_ANNIV_CONTACT) {
          $DB->DbQuery("UPDATE ${PREFIX_TABLE}calepin SET cal_rappel_ok=".date("Y")." WHERE cal_id=".$enr['id']." AND cal_util_id=".$enr['idDestinataire']);
        }
        if ($enr['id']!=$noteID) {
          if (count($destMail)>0) {
            $nbMail += (envoiMail($nomEmetteur,$mailEmetteur,$destMail,$sujetMail,$corpsMail)) ? 1 : 0;
          }
          $noteID = $enr['id'];
          $destMail  = array();
          $mailEmetteur = $enr['expEmail'];
          $nomEmetteur = $enr['expNom'];
          if ($typeRappel==RAPPEL_NOTE) {
            // Info sur le mail pour les destinataires "Phenix" de la note
            $sujetMail = date("[d/m/y - H\hi]",$tsEvent)." ".$libEvent;
            $corpsMail = nl2br("<HTML><BODY>".sprintf(trad("INFO_NOTE_DEBUTE"), date("d/m/Y",$tsEvent), date("H\hi",$tsEvent))."\n\n<U>".trad("INFO_LIBELLE")."</U>:&nbsp;".$libEvent."\n".((!empty($enr['age_lieu'])) ? "<U>".trad("INFO_EMPLACEMENT")."</U>:&nbsp;".$enr['age_lieu']."\n" : "").((!empty($enr['age_detail'])) ? "<U>".trad("INFO_DETAIL")."</U>:&nbsp;".$enr['age_detail']."\n" : "").((!empty($enr['nomContact'])) ? "<U>".trad("INFO_CONTACT")."</U>:&nbsp;".$enr['nomContact'] : "").signatureMail());
            // A la premiere lecture de la note, si age_email_contact=1 ET (cal_email OU cal_emailpro non vide)
            // -> Envoi du mail au contact associe puis desactivation du rappel au contact associe pour la note
            if ($enr['age_email_contact']==1 && (!empty($enr['cal_email']) || !empty($enr['cal_emailpro']))) {
              $corpsMailContact = nl2br("<HTML><BODY>".sprintf(trad("INFO_NOTE_CONTACT_DEBUTE"), date("d/m/Y",$tsEvent), date("H\hi",$tsEvent), $enr['expNom'])."\n\n<U>".trad("INFO_LIBELLE")."</U>:&nbsp;".$libEvent."\n".((!empty($enr['age_lieu'])) ? "<U>".trad("INFO_EMPLACEMENT")."</U>:&nbsp;".$enr['age_lieu']."\n" : "").((!empty($enr['age_detail'])) ? "<U>".trad("INFO_DETAIL")."</U>:&nbsp;".$enr['age_detail'] : "").signatureMail());
              // Envoi en priorite a l'adresse email "personnelle" du contact, si non renseignee, envoi sur l'adresse professionnelle
              $destMailContact = (!empty($enr['cal_email'])) ? array($enr['cal_email']) : array($enr['cal_emailpro']);
              $nbMail += (envoiMail($nomEmetteur,$mailEmetteur,$destMailContact,$sujetMail,$corpsMailContact)) ? 1 : 0;
              // Pour le rappel au contact associe a une note, lorsque le rappel a ete traite (c'est le cas ici),
              // -> on passe la valeur a 2 (1 signifiant -> A traiter et 0 -> Pas de rappel)
              $DB->DbQuery("UPDATE ${PREFIX_TABLE}agenda SET age_email_contact=2 WHERE age_id=".$enr['id']);
            }
          } else {
            $sujetMail = "[".$enr['dateEvent']."] ".$libEvent;
            $tabDate = explode("-",$enr['dateNaissance']);
            $age = calculAge($tabDate,$tsEvent);
            $corpsMail = nl2br("<HTML><BODY>".sprintf(trad("INFO_DETAIL_AGE"), $enr['nomAnniv'], $age, $enr['dateEvent']).signatureMail());
          }
        }
        if ($enr['envoiMail']==1 && !empty($enr['destEmail'])) {
          $destMail[] = $enr['destEmail'];
        }
      }
    }
    if (count($destMail)>0) {
      $nbMail += (envoiMail($nomEmetteur,$mailEmetteur,$destMail,$sujetMail,$corpsMail)) ? 1 : 0;
    }
  }

  // Recherche des rappels de note a notifier pour tous les utilisateurs et aux contacts associes
  gereRappel(RAPPEL_NOTE);
  // Recherche des anniversaires a notifier pour tous les utilisateurs
  gereRappel(RAPPEL_ANNIV);
  // Recherche des anniversaires des contacts a notifier pour tous les utilisateurs
  gereRappel(RAPPEL_ANNIV_CONTACT);

  // CORPS DE LA PAGE
  echo ("<!doctype html public \"-//w3c//dtd html 4.0 transitional//en\">
<HTML>
<HEAD>
  ".$refreshPage."<META http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
  <LINK rel=\"stylesheet\" type=\"text/css\" href=\"css/agenda_css.php?id=".$APPLI_STYLE."\">
  <TITLE>".trad("INFO_TITRE_PAGE")."</TITLE>\n");

  $onLoad = "";
  if ($idUser>0) {
    // Recherche des rappels a notifier pour l'utilisateur connecte
    $DB_CX->DbQuery("SELECT info_id FROM ${PREFIX_TABLE}information WHERE info_destinataire_id=".$idUser." AND info_heure_rappel<=".gmmktime());
    if ($DB_CX->DbNumRows()) {
      $onLoad = " onLoad=\"javascript: alerte();\"";
      echo ("  <SCRIPT language=\"JavaScript\">
  <!--
    var infoWin;
    function alerte() {
      if (window.showModalDialog) {
        var _options = 'dialogWidth:400px;dialogHeight:".min(95+50*$DB_CX->DbNumRows(),270)."px;center:1;scroll:1;help:0;status:0;';
        infoWin = window.showModalDialog(\"info_popup.php?sid=".$sid."\",\"EventWin_".$sid."\",_options);
      } else {
        var _width = 400, _height = ".min(100+60*$DB_CX->DbNumRows(),290).";
        var posX = (Math.max(screen.width,_width)-_width)/2;
        var posY = (Math.max(screen.height,_height)-_height)/2;
        var nVer = navigator.appVersion.split(';');
        var _position = (!(nVer[1].match('MSIE'))) ? ',top=' + posY + ',left=' + posX : ',screenY=' + posY + ',screenX=' + posX;
        infoWin = window.open('info_popup.php?sid=".$sid."','EventWin_".$sid."','dependent=1,menubar=0,toolbar=0,location=0,directories=0,status=0,scrollbars=1,resizable=0,width=' + _width + ',height=' + _height + _position);
      }
    }
  //-->
  </SCRIPT>\n");
    }
  }
  echo ("</HEAD>

<BODY".$onLoad.">
  ".sprintf(trad("INFO_RECAPITULATIF"), $nbRappel, $nbMail));

  // Appel au fichier de gestion des sauvegardes automatiques de la base de donnees
  include ("inc/xtdump_webcron.php");

  echo ("\n</BODY>
</HTML>");

  // Fermeture SMTP
  if ($classSMTPLoaded)
    $mailer->smtp->quit();

  // Fermeture BDD
  $DB_CX->DbDeconnect();
?>
