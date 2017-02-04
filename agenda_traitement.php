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

  include("inc/param.inc.php");
  if (isset($sid)) {
    include("inc/fonctions.inc.php");
    include('inc/class.mailer.php');
    include("inc/html.inc.php");
    $classMailerLoaded = $classSMTPLoaded = false;
  } else {
    Header("location: deconnexion.php?msg=5");
    exit;
  }

  $idUser = Session_ok($sid);

  include("lang/$APPLI_LANGUE.php");

  $idAge += 0;

  $sTmp = "";

/*--------------------------------------------
              GESTION DES NOTES
--------------------------------------------*/
if ($ztFrom == "note") {
  if ($tcMenu>=_MENU_DISP_HEBDO)
    $tcMenu = $tcPlg;
  if ($ztAction == "INSERT" || $ztAction == "UPDATE") {
    $tabDate = explode("/",$ztDateNote);
    $ztDateForm = $tabDate[2]."-".$tabDate[1]."-".$tabDate[0];

    $zlHeureDebut=str_replace(",",".",$zlHeureDebut);
    $zlHeureFin=str_replace(",",".",$zlHeureFin);
    // Conversion en utc en fonction du timezone
    $decalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$tabDate[1],$tabDate[0],$tabDate[2]));
    if ($decalageHoraire!=0) {
      // Calcule heure de debut en utc
      $tzNote = mktime(floor($zlHeureDebut)-floor($decalageHoraire), ($zlHeureDebut*60)%60-($decalageHoraire*60)%60, 0, $tabDate[1], $tabDate[0], $tabDate[2]);
      $ztDateNoteD = date("d/m/Y",$tzNote);
      $zlHeureDebut = date("H",$tzNote).".".date("i",$tzNote)*100/60;
      // Calcule heure de fin en utc
      $tzNoteF = mktime(floor($zlHeureFin)-floor($decalageHoraire), ($zlHeureFin*60)%60-($decalageHoraire*60)%60, 0, $tabDate[1], $tabDate[0], $tabDate[2]);
      $ztDateNoteF = date("d/m/Y",$tzNoteF);
      $zlHeureFin = date("H",$tzNoteF).".".date("i",$tzNoteF)*100/60;
      if ($zlHeureFin == "00.00") $zlHeureFin = "24.00";
      $tabDate = explode("/",$ztDateNoteD);
    }
    $tzDst = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$tabDate[1],$tabDate[0],$tabDate[2])) - $tzGmt;

    $ztDate = $tabDate[2]."-".$tabDate[1]."-".$tabDate[0];
    $zlPeriodicite += 0;
    $zlContactAssocie += 0;
    $periode1 = $periode2 = $periode3 = $periode4 = 0;
    switch ($zlPeriodicite) {
      case 2 :
        if ($rdQ == 1) {
          $ztQ = $periode2 = (floor($ztQ)>0) ? floor($ztQ) : 1;
        } else {
          $rdQ = 2;
        }
        $periode1 = $rdQ;
        break;
      case 3 :
        $ztH = $periode1 = (floor($ztH)>0) ? floor($ztH) : 1;
        // Creation d'un tableau des jours de la semaine au format PHP ie. du Dimanche(0) au Samedi(6)
        $aSemaineType = array();
        //Stockage de la semaine type au format PHP qui est utilisee pour creer la note
        $periode2 = "";
        for ($i=0;$i<7;$i++) {
          $aSemaineType[$i] = (!$i) ? $bt7 + 0 : ${"bt".$i} + 0;
          $periode2 .= $aSemaineType[$i];
        }
        $periode2 += 0; // On retransforme la chaine en entier pour enlever les 0 devants
        break;
      case 4 :
        if ($rdM == 1) {
          $periode2 = $zlM1;
        } else {
          $rdM = 2; $periode2 = $zlM2; $periode3 = $zlM3;
        }
        $periode1 = $rdM;
        $ztM = $periode4 = (floor($ztM)>0) ? floor($ztM) : 1;
        break;
      case 5 :
        if ($rdA == 1) {
          $periode2 = $zlA1; $periode3 = $zlA2;
        } else {
          $rdA = 2; $periode2 = $zlA3; $periode3 = $zlA4; $periode4 = $zlA5;
        }
        $periode1 = $rdA;
        break;
      default : $zlPeriodicite = 1;
    }
    if ($rdPlage == 2 && $zlPeriodicite > 1) {
      $nbOccurence = 0;
      list($zlP1,$zlP2,$zlP3) = explode("/",$ztDateFin);
      if (!checkdate($zlP2,$zlP1,$zlP3))
        $zlP1 = date("t", mktime(12,0,0,$zlP2,1,$zlP3));
      $dateMax = mktime(12,0,0,$zlP2,$zlP1,$zlP3);
    } elseif ($zlPeriodicite > 1) {
      $ztP += 0;
      $rdPlage = 1;
      $nbOccurence = min($ztP,99);
      $dateMax = 0;
    } else {
      $rdPlage = 1;
      $nbOccurence = 10;
      $dateMax = 0;
    }
    if ($rdRappel != 2) {
      $zlR1 = 0;
      $zlR2 = 1;
      $ckEmail = 0;
      $ckEmailContact = 0;
    } else {
      if ($ckEmail != 1) {
        $ckEmail = 0;
      }
      if ($ckEmailContact != 1 || $zlContactAssocie == "0") {
        $ckEmailContact = 0;
      }
    }
    if ($rdPrive != 1)
      $rdPrive = 0;
    if ($rdDispo != 1)
      $rdDispo = 0;
    if ($ckTypeNote!=3)
      $ckTypeNote=2;
    $hNote = floor($zlHeureDebut);
    $mNote = ($zlHeureDebut*60)%60;
    $tsNow = mktime(gmdate("H"),gmdate("i"),0,gmdate("n"),gmdate("j"),gmdate("Y"));
    $tsAlert = mktime(gmdate("H"),gmdate("i")+($zlR1*$zlR2),0,gmdate("n"),gmdate("j"),gmdate("Y"));
    $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0],$tabDate[2]);
    $endNote = ($tsNote > $tsNow) ? 0 : 1;
    $alert = ($tsNote > $tsAlert && $zlR1) ? 0 : 1;
    //Liste des personnes concernees
    $idParticipant = explode("+", $ztParticipant);
  }

  // Recuperation pour les alertes par mail du nom et de l'adresse mail de l'utilisateur courant
  $DB_CX->DbQuery("SELECT CONCAT(".$FORMAT_NOM_UTIL."), util_email FROM ${PREFIX_TABLE}utilisateur WHERE util_id=".$idUser);
  $sNomExpediteur = $DB_CX->DbResult(0,0);
  $sMailExpediteur = $DB_CX->DbResult(0,1);

  // Verification de la superposition des notes
  if ($ztAction == "INSERT" || ($ztAction == "UPDATE" && $idAge)) {
    // Test de l'existence d'une note pour la plage horaire concernee
    // pour les personnes concernees par la note (autre que le createur)
    $sql  = "SELECT DISTINCT(aco_util_id) AS acoUtilId FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne ";
    $sql .= "WHERE aco_age_id=age_id AND aco_util_id!=".$idUser." AND age_aty_id=2 AND age_date='".$ztDate."' AND age_id!=".$idAge;
    $sql .= " AND ((age_heure_debut<=".$zlHeureDebut." AND age_heure_fin>".$zlHeureDebut.")";
    $sql .= " OR (age_heure_debut>=".$zlHeureDebut." AND age_heure_fin<=".$zlHeureFin.")";
    $sql .= " OR (age_heure_fin>=".$zlHeureFin." AND age_heure_debut<".$zlHeureFin.")) ORDER BY aco_util_id";
    $DB_CX->DbQuery($sql);
    $noteUser="";
    //Trie du tableau par ordre croissant
    //Pour ensuite faire avancer l'indice de depart de la boucle FOR
    //en fonction du dernier participant trouve
    sort($idParticipant);
    reset($idParticipant);
    $iDepart=0;
    $cpTour=0;
    while ($enr = $DB_CX->DbNextRow()) {
      $ok=false;
      for ($nb=$iDepart;$nb<count($idParticipant) && !$ok;$nb++) {
        if ($idParticipant[$nb]==$enr['acoUtilId']) {
          $noteUser .= ",".$enr['acoUtilId'];
          $ok = true;
        }
        $cpTour++;
      }
      $iDepart=$nb;
    }
    // Liste des utilisateurs concernes par la superposition de note a ajouter dans l'url en bas de page
    $sTmp = (!empty($noteUser)) ? "&lSup=".substr($noteUser,1) : "";
  }

  // Recuperation des informations lors de la suppression et la mise a jour
  // Permet ainsi l'envoi de mail pour toute suppression d'une note aux personnes concernees
  // Et aussi en cas de modification de la note et de suppression d'une personne concernee
  if (($ztAction == "DELETE" || $ztAction == "UPDATE") && $idAge) {
    // Construction de la liste des personnes qui ETAIENT concernees par l'ANCIENNE note
    // On ne retient que les utilisateurs (autres que l'auteur) qui ont choisi d'etre informe par email
    $DB_CX->DbQuery("SELECT util_id, util_email FROM ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur WHERE aco_age_id=".$idAge." AND aco_util_id!=".$idUser." AND util_id=aco_util_id AND util_alert_affect='O'");
    $aTabConcerne = array();
    $sOldDestMail = array();
    while ($enr = $DB_CX->DbNextRow()) {
      if (!empty($enr['util_email'])) {
        $aTabConcerne[$enr['util_id']] = $enr['util_email'];
        $sOldDestMail[] = $enr['util_email'];
      }
    }
    if ($ztAction == "UPDATE") {
      // Construction de la liste des personnes qui SONT concernees par la NOUVELLE note
      // On ne retient que les utilisateurs (autres que l'auteur) qui ont choisi d'etre informe par email
      $aTabNvConcerne = array();
      for ($nb=0;$nb < count($idParticipant);$nb++) {
        if ($idParticipant[$nb]!=$idUser) {
          $DB_CX->DbQuery("SELECT util_email FROM ${PREFIX_TABLE}utilisateur WHERE util_id=".$idParticipant[$nb]." AND util_alert_affect='O'");
          // Test pour savoir si cet utilisateur a renseigne son adresse email
          if ($DB_CX->DbResult(0,0)!="")
            $aTabNvConcerne[$idParticipant[$nb]] = $DB_CX->DbResult(0,0);
        }
      }
      // Construction de la liste des personnes qui NE SONT PLUS concernees par la NOUVELLE note
      // Permet d'envoyer un mail de suppression si une personne est retiree de la note
      $aTmp = array_diff($aTabConcerne, $aTabNvConcerne);
      // Concatenation des emails des personnes de la liste ci-dessus
      $sSupDestMail = array();
      while(list($sCle,$sValeur)=each($aTmp)) {
        $sSupDestMail[] = $sValeur;
      }
    }
    // Recuperation des informations de la note avant la suppression
    $DB_CX->DbQuery("SELECT DATE_FORMAT(age_date,'%d/%m/%Y'), age_heure_debut, age_libelle, age_lieu, age_detail FROM ${PREFIX_TABLE}agenda WHERE age_id=".$idAge);
    $sDate = $DB_CX->DbResult(0,0);
    // Formatage de l'heure de la note pour affichage dans le mail
    $sHeureNoteSupp = afficheHeure(floor($DB_CX->DbResult(0,1)), $DB_CX->DbResult(0,1), "H\hi");
    $sLibelle = $DB_CX->DbResult(0,2);
    $sLieu = $DB_CX->DbResult(0,3);
    $sDetail = $DB_CX->DbResult(0,4);
    // Construction du sujet du mail
    $sSujet = trad("TRAITEMENT_NOTIF_SUPP");
    // Construction du corps du mail
    if ($flag != 2) {
      // L'auteur d'une note la supprime -> on informe les utilisateurs concernes
      $sCorps = nl2br("<HTML><BODY>".sprintf(trad("TRAITEMENT_SUPP_J"),$sNomExpediteur,$sDate,$sHeureNoteSupp)."\n\n<U>".trad("TRAITEMENT_LIBELLE")."</U>:&nbsp;".$sLibelle."\n".((!empty($sLieu)) ? "<U>".trad("TRAITEMENT_EMPLACEMENT")."</U>:&nbsp;".$sLieu."\n" : "").((!empty($sDetail)) ? "<U>".trad("TRAITEMENT_DETAIL")."</U>:&nbsp;".$sDetail : "").signatureMail());
    } elseif ($flag == 2 && $AUTORISE_SUPPR) {
      // Un utilisateur supprime une note qui lui avait ete affectee -> on informe le createur de la note
      $DB_CX->DbQuery("SELECT util_email FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}utilisateur WHERE age_id=".$idAge." AND util_id=age_util_id");
      $sAuteurNote = array();
      if ($DB_CX->DbResult(0,0) != "")
        $sAuteurNote[] = $DB_CX->DbResult(0,0);
      $sCorps = nl2br("<HTML><BODY>".sprintf(trad("TRAITEMENT_SUPP_AFFECT"),$sNomExpediteur,$sDate,$sHeureNoteSupp)."\n\n<U>".trad("TRAITEMENT_LIBELLE")."</U>:&nbsp;".$sLibelle."\n".((!empty($sLieu)) ? "<U>".trad("TRAITEMENT_EMPLACEMENT")."</U>:&nbsp;".$sLieu."\n" : "").((!empty($sDetail)) ? "<U>".trad("TRAITEMENT_DETAIL")."</U>:&nbsp;".$sDetail : "").signatureMail());
    }
  }

  if ($ztAction == "INSERT") {
    $sd = $ztDateForm;
    $dateCreation = gmdate("Y-m-d H:i:s", time());
    $sql = "INSERT INTO ${PREFIX_TABLE}agenda (age_mere_id,age_util_id,age_aty_id,age_date,age_heure_debut,age_heure_fin,age_ape_id, age_periode1, age_periode2, age_periode3, age_periode4, age_plage, age_plage_duree, age_libelle, age_detail, age_rappel, age_rappel_coeff, age_email, age_prive, age_couleur, age_nb_participant, age_createur_id, age_disponibilite, age_date_creation, age_date_modif, age_modificateur_id, age_lieu, age_cal_id, age_email_contact) ";
    $sql .= "VALUES (0,".$idUser.",".$ckTypeNote.",'".$ztDate."',".$zlHeureDebut.",".$zlHeureFin.",".$zlPeriodicite.",".$periode1.",".$periode2.",".$periode3.",".$periode4.",".$rdPlage.",".($nbOccurence + $dateMax).",'".$ztLibelle."','".$ztDetail."',".$zlR1.",".$zlR2.",".$ckEmail.",".$rdPrive.",'".$zlCouleur."',".count($idParticipant).",".$idUser.",".$rdDispo.",'".$dateCreation."','".$dateCreation."',".$idUser.",'".$ztLieu."',".$zlContactAssocie.",".$ckEmailContact.")";
    $DB_CX->DbQuery($sql);
    $idAge = $DB_CX->DbInsertID();

    // Enregistrement des personnes concernees
    for ($nb=0;$nb < count($idParticipant);$nb++)
      $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}agenda_concerne VALUES (".$idAge.",".$idParticipant[$nb].",".$alert.",".$endNote.")");
    $msg=8;

    //Si l'utilisateur a clique sur le bouton Recommencer,
    //on enregistre les parametres pour qu'il soit renvoye vers la page de creation de note
    if ($ztRecommence == "OUI") {
      $sTmp .= "&tcType="._TYPE_NOTE."&tcPlg=".$tcMenu;
    }
  }

  elseif ($ztAction == "UPDATE" && $idAge) {
    if ($zlPeriodicite == 1)
      $sd = $ztDateForm;
    $liste = "0";
    if ($edit!="occ") {
      $DB_CX->DbQuery("SELECT DISTINCT age_id, age_date_creation FROM ${PREFIX_TABLE}agenda WHERE age_mere_id=".$idAge);
      while ($enr = $DB_CX->DbNextRow()) {
        $liste .= ",".$enr['age_id'];
        $dateCreation = $enr['age_date_creation'];
      }
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda WHERE age_id IN (".$liste.")");
    }
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id IN (".$liste.",".$idAge.")");

    $sql = "UPDATE ${PREFIX_TABLE}agenda ";
    $sql .= "SET age_aty_id=".$ckTypeNote.",";
    $sql .= " age_date='".$ztDate."',";
    $sql .= " age_heure_debut=".$zlHeureDebut.",";
    $sql .= " age_heure_fin=".$zlHeureFin.",";
    if ($edit!="occ") {
      $sql .= " age_ape_id=".$zlPeriodicite.",";
      $sql .= " age_periode1=".$periode1.",";
      $sql .= " age_periode2=".$periode2.",";
      $sql .= " age_periode3=".$periode3.",";
      $sql .= " age_periode4=".$periode4.",";
      $sql .= " age_plage=".$rdPlage.",";
      $sql .= " age_plage_duree=".($nbOccurence + $dateMax).",";
    }
    $sql .= " age_libelle='".$ztLibelle."',";
    $sql .= " age_detail='".$ztDetail."',";
    $sql .= " age_rappel=".$zlR1.",";
    $sql .= " age_rappel_coeff=".$zlR2.",";
    $sql .= " age_email=".$ckEmail.",";
    $sql .= " age_email_contact=".$ckEmailContact.",";
    $sql .= " age_prive=".$rdPrive.",";
    $sql .= " age_couleur='".$zlCouleur."',";
    $sql .= " age_nb_participant=".count($idParticipant).",";
    $sql .= " age_disponibilite=".$rdDispo.",";
    $sql .= " age_date_modif='".gmdate("Y-m-d H:i:s", time())."',";
    $sql .= " age_modificateur_id=".$idUser.",";
    $sql .= " age_lieu='".$ztLieu."',";
    $sql .= " age_cal_id=".$zlContactAssocie." ";
    $sql .= "WHERE age_id=".$idAge;
    if ($droit_NOTES < _DROIT_NOTE_MODIF_CREATION)
      $sql .= " AND age_util_id=".$idUser;
    $DB_CX->DbQuery($sql);
    $msg=9;

    // Enregistrement des personnes concernees
    for ($nb=0;$nb < count($idParticipant);$nb++)
      $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}agenda_concerne VALUES (".$idAge.",".$idParticipant[$nb].",".$alert.",".$endNote.")");
  }

  elseif ($ztAction == "DELETE" && $idAge) {
    $flag += 0;
    if ($flag == 2 && $AUTORISE_SUPPR) {
      //Suppression d'une note affectee
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id=".$idAge." AND aco_util_id=".$idUser);
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}information WHERE info_age_id=".$idAge." AND info_destinataire_id=".$idUser);
      //Recherche s'il reste des personnes concernees par cette note
      $DB_CX->DbQuery("SELECT aco_util_id FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id=".$idAge);
      //si NON : on efface la note
      if (!$DB_CX->DbNumRows()) {
        $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda WHERE age_id=".$idAge);
      } else {
        //si OUI : on reajuste le nombre de participant (pour l'appropriation)
        $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}agenda SET age_nb_participant = ".$DB_CX->DbNumRows()." WHERE age_id=".$idAge);
      }

      //On informe l'auteur de la note
      if (count($sAuteurNote)>0)
        envoiMail($sNomExpediteur, $sMailExpediteur, $sAuteurNote, $sSujet, $sCorps);
    } elseif ($flag == 1) {
      //Suppression de la totalite d'une note par son auteur
      $DB_CX->DbQuery("SELECT DISTINCT age_id FROM ${PREFIX_TABLE}agenda WHERE (age_id=".$idAge." OR age_mere_id=".$idAge.")".(($droit_NOTES < _DROIT_NOTE_COMPLET) ? " AND age_util_id=".$idUser :""));
      $liste = "0";
      while ($enr = $DB_CX->DbNextRow())
        $liste .= ",".$enr['age_id'];
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda WHERE age_id IN (".$liste.")");
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id IN (".$liste.")");
      //On informe les personnes concernees que l'auteur vient de supprimer la note qu'il avait cree
      if ($DB_CX->DbAffectedRows()>0 && count($sOldDestMail)>0) {
        envoiMail($sNomExpediteur, $sMailExpediteur, $sOldDestMail, $sSujet, $sCorps);
      }
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}information WHERE info_age_id IN (".$liste.")");
      $msg=10;
    } else {
      //Suppression d'une occurence d'une note par son auteur
      $DB_CX->DbQuery("SELECT MIN(age_id) FROM ${PREFIX_TABLE}agenda WHERE age_mere_id=".$idAge.(($droit_NOTES >= _DROIT_NOTE_COMPLET) ? " AND age_util_id=".$idUser :""));
      $newIdAge = $DB_CX->DbResult(0,0) + 0;
      if ($newIdAge) {
        $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}agenda SET age_mere_id=".$newIdAge." WHERE age_mere_id=".$idAge);
        $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}agenda SET age_mere_id=0 WHERE age_id=".$newIdAge);
      }
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda WHERE age_id=".$idAge.(($droit_NOTES < _DROIT_NOTE_COMPLET) ? " AND age_util_id=".$idUser :""));
      if ($DB_CX->DbAffectedRows()>0) {
        $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id=".$idAge);
       //On informe les personnes concernees que l'auteur vient de supprimer une occurence d'une note qu'il avait cree
        if ($DB_CX->DbAffectedRows()>0 && count($sOldDestMail)>0) {
          envoiMail($sNomExpediteur, $sMailExpediteur, $sOldDestMail, $sSujet, $sCorps);
        }
        $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}information WHERE info_age_id=".$idAge);
      }
      $msg=11;
    }
    $idAge = 0;
  }

  elseif ($ztAction == "APPROPRIATION" && $idAge) {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}agenda SET age_util_id=".$idUser." WHERE age_id=".$idAge);
    $idAge = 0;
  }
  if ($idAge) {
    // Information par mail des personnes a qui on a affecte une note
    $DB_CX->DbQuery("SELECT util_email FROM ${PREFIX_TABLE}utilisateur, ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id=".$idAge." AND util_id=aco_util_id AND util_id!=".$idUser." AND util_alert_affect='O'");
    if ($DB_CX->DbNumRows()) {
      $destMail = array();
      while ($DB_CX->DbNextRow())
        $destMail[] = $DB_CX->Row[0];
      $corpsMail  = nl2br("<HTML><BODY>".sprintf(trad("TRAITEMENT_CREER_NOTE"),$sNomExpediteur).(($zlPeriodicite>1) ? trad("TRAITEMENT_RECURRENTE") : trad("TRAITEMENT_JOURNEE"))." <B>".date(trad("TRAITEMENT_FORMAT_DATE"),$tsNote+$decalageHoraire*3600)."</B>\n\n<U>".trad("TRAITEMENT_LIBELLE")."</U>:&nbsp;".stripslashes($ztLibelle)."\n".((!empty($ztLieu)) ? "<U>".trad("TRAITEMENT_EMPLACEMENT")."</U>:&nbsp;".stripslashes($ztLieu)."\n" : "").((!empty($ztDetail)) ? "<U>".trad("TRAITEMENT_DETAIL")."</U>:&nbsp;".stripslashes($ztDetail) : "").signatureMail());
      envoiMail($sNomExpediteur, $sMailExpediteur, $destMail, trad("TRAITEMENT_NOTIF_AJOUT"), $corpsMail);
    }
    // Si la liste des destinataires non conserves est non nulle alors on les avertit
    if (count($sSupDestMail)>0) {
      envoiMail($sNomExpediteur, $sMailExpediteur, $sSupDestMail, $sSujet, $sCorps);
    }
    // Requete generique
    $sql = "INSERT INTO ${PREFIX_TABLE}agenda (age_mere_id,age_util_id,age_aty_id,age_date,age_heure_debut,age_heure_fin,age_ape_id, age_periode1, age_periode2, age_periode3, age_periode4, age_plage, age_plage_duree, age_libelle, age_detail, age_rappel, age_rappel_coeff, age_email, age_prive, age_couleur, age_nb_participant, age_createur_id, age_disponibilite, age_date_creation, age_date_modif, age_modificateur_id, age_lieu, age_cal_id, age_email_contact) ";
    $sql .= "VALUES (".$idAge.",".$idUser.",".$ckTypeNote.",'{theNewDate}',{theBeginHour},{theEndHour},".$zlPeriodicite.",".$periode1.",".$periode2.",".$periode3.",".$periode4.",".$rdPlage.",".($nbOccurence + $dateMax).",'".$ztLibelle."','".$ztDetail."', ".$zlR1.",".$zlR2.",".$ckEmail.",".$rdPrive.",'".$zlCouleur."',".count($idParticipant).",".$idUser.",".$rdDispo.",'".$dateCreation."','".gmdate("Y-m-d H:i:s", time())."',".$idUser.",'".$ztLieu."',".$zlContactAssocie.",".$ckEmailContact.")";
    if ($rdPlage == 1) {
      // Repetition en nombre d'occurence
      switch ($zlPeriodicite) {
        case 2 : // Quotidienne
          if ($rdQ == 1) {
            for ($i=1;$i<$nbOccurence;$i++) {
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+($i*$ztQ),$tabDate[2]);
              insertOccurence();
            }
          } else {
            for ($i=1;$i<$nbOccurence;$i++) {
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+$i,$tabDate[2]);
              if (date("w",$tsNote)!=0 && date("w",$tsNote)!=6) {
                insertOccurence();
              } else
                $nbOccurence++;
            }
          }
          break;
        case 3 : // Hebdomadaire
          $i=1; $nbAjout = 1;
          while ($nbAjout<$nbOccurence) {
            $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+$i,$tabDate[2]);
            if (date("w",$tsNote)==1 && $ztH>1) { // Les lundi on verifie les sauts de semaine
              $i = $i+(7*($ztH-1));
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+$i,$tabDate[2]);
            }
            $i++;
            if ($aSemaineType[date("w",$tsNote)]==1) {
              insertOccurence();
              $nbAjout++;
            }
          }
          break;
        case 4 : // Mensuelle
          for ($i=1;$i<$nbOccurence;$i++) {
            $jSelect = ($rdM == 1) ? $zlM1 : calcJour($zlM2,$zlM3,$tabDate[1]+($ztM*$i),$tabDate[2]);
            $tsNote = mktime($hNote,$mNote,0,$tabDate[1]+($ztM*$i),$jSelect,$tabDate[2]);
            insertOccurence();
          }
          break;
        case 5 : // Annuelle
          for ($i=1;$i<$nbOccurence;$i++) {
            if ($rdA == 1) {
              $jSelect = $zlA1;
              if (!checkdate($zlA2,$jSelect,$tabDate[2]+$i))
                $jSelect = date("t", mktime(12,0,0,$zlA2,1,$tabDate[2]+$i));
              $tsNote = mktime($hNote,$mNote,0,$zlA2,$jSelect,$tabDate[2]+$i);
            } else
              $tsNote = mktime($hNote,$mNote,0,$zlA5,calcJour($zlA3,$zlA4,$zlA5,$tabDate[2]+$i),$tabDate[2]+$i);
            insertOccurence();
          }
          break;
      }
    } else {
      // Repetition avec une date de fin
      $i = 1;
      switch ($zlPeriodicite) {
        case 2 : // Quotidienne
          if ($rdQ == 1) {
            $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+($ztQ*$i++),$tabDate[2]);
            while ($tsNote <= $dateMax) {
              insertOccurence();
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+($ztQ*$i++),$tabDate[2]);
            }
          } else {
            $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+($i++),$tabDate[2]);
            while ($tsNote <= $dateMax) {
              if (date("w",$tsNote)!=0 && date("w",$tsNote)!=6) {
                insertOccurence();
              }
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+($i++),$tabDate[2]);
            }
          }
          break;
        case 3 : // Hebdomadaire
          $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+$i,$tabDate[2]);
          $stop = false;
          while ($tsNote <= $dateMax) {
            if (date("w",$tsNote)==1 && $ztH>1) { // Les lundi on verifie les sauts de semaine
              $i = $i+(7*($ztH-1));
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+$i,$tabDate[2]);
              $stop = ($tsNote > $dateMax);
            }
            if (!$stop) {
              if ($aSemaineType[date("w",$tsNote)]==1) {
                insertOccurence();
              }
              $tsNote = mktime($hNote,$mNote,0,$tabDate[1],$tabDate[0]+(++$i),$tabDate[2]);
            }
          }
          break;
        case 4 : // Mensuelle
          $jSelect = ($rdM == 1) ? $zlM1 : calcJour($zlM2,$zlM3,$tabDate[1]+$ztM,$tabDate[2]);
          $tsNote = mktime($hNote,$mNote,0,$tabDate[1]+$ztM,$jSelect,$tabDate[2]);
          while ($tsNote <= $dateMax) {
            insertOccurence();
            $i++;
            $jSelect = ($rdM == 1) ? $zlM1 : calcJour($zlM2,$zlM3,$tabDate[1]+($ztM*$i),$tabDate[2]);
            $tsNote = mktime($hNote,$mNote,0,$tabDate[1]+($ztM*$i),$jSelect,$tabDate[2]);
          }
          break;
        case 5 : // Annuelle
          if ($rdA == 1) {
            $jSelect = $zlA1;
            if (!checkdate($zlA2,$jSelect,$tabDate[2]+$i))
              $jSelect = date("t", mktime(12,0,0,$zlA2,1,$tabDate[2]+$i));
            $tsNote = mktime($hNote,$mNote,0,$zlA2,$jSelect,$tabDate[2]+($i++));
          } else
            $tsNote = mktime($hNote,$mNote,0,$zlA5,calcJour($zlA3,$zlA4,$zlA5,$tabDate[2]+$i),$tabDate[2]+($i++));
          while ($tsNote <= $dateMax) {
            insertOccurence();
            if ($rdA == 1) {
              $jSelect = $zlA1;
              if (!checkdate($zlA2,$jSelect,$tabDate[2]+$i))
                $jSelect = date("t", mktime(12,0,0,$zlA2,1,$tabDate[2]+$i));
              $tsNote = mktime($hNote,$mNote,0,$zlA2,$jSelect,$tabDate[2]+($i++));
            } else
              $tsNote = mktime($hNote,$mNote,0,$zlA5,calcJour($zlA3,$zlA4,$zlA5,$tabDate[2]+$i),$tabDate[2]+($i++));
          }
          break;
      }
    }
  }
}


/*--------------------------------------------
           GESTION DES ANNIVERSAIRES
--------------------------------------------*/
elseif ($ztFrom == "anniv") {
  if ($tcMenu>=_MENU_DISP_HEBDO)
    $tcMenu = _MENU_PLG_QUOT;
  if ($ztAction != "DELETE") {
    $tabDate = explode("/",$ztDate);
    $dateAnnivOK = false;
    if (checkdate($tabDate[1],$tabDate[0],$tabDate[2])) {
      $ztDate = $tabDate[2]."-".$tabDate[1]."-".$tabDate[0];
      $dateAnnivOK = true;
    } else {
      $msg=16;
      $sTmp .= "&tcType="._TYPE_ANNIV;
    }
  }
  if ($ztAction == "INSERT" && $dateAnnivOK) {
    $sd = date("Y")."-".$tabDate[1]."-".$tabDate[0];
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}agenda (age_util_id,age_aty_id,age_date,age_libelle,age_createur_id,age_date_creation,age_modificateur_id,age_date_modif) VALUES (".$idUser.",1,'".$ztDate."','".$ztLibelle."',".$idUser.",'".gmdate("Y-m-d H:i:s", time())."',".$idUser.",'".gmdate("Y-m-d H:i:s", time())."')");
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}agenda_concerne VALUES (".$DB_CX->DbInsertID().",".$idUser.",1,0)");
    $msg=12;
  }

  elseif ($ztAction == "UPDATE" && $idAge && $dateAnnivOK) {
    $sd = date("Y")."-".$tabDate[1]."-".$tabDate[0];
    $sql = "UPDATE ${PREFIX_TABLE}agenda ";
    $sql .= "SET age_date='".$ztDate."',";
    $sql .= " age_libelle='".$ztLibelle."',";
    $sql .= " age_date_modif='".gmdate("Y-m-d H:i:s", time())."',";
    $sql .= " age_modificateur_id=".$idUser." ";
    $sql .= "WHERE age_id=".$idAge." AND age_util_id=".$idUser;
    $DB_CX->DbQuery($sql);
    $msg=13;
  }

  elseif ($ztAction == "DELETE" && $idAge) {
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda WHERE age_id=".$idAge." AND age_util_id=".$idUser);
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}agenda_concerne WHERE aco_age_id=".$idAge." AND aco_util_id=".$idUser);
    $msg=14;
  }
}


/*--------------------------------------------
           GESTION DES EVENEMENTS
--------------------------------------------*/
elseif ($ztFrom == "evenement") {
  $sTmp .= "&tcType="._TYPE_EVENEMENT;
  if ($ztAction != "DELETE") {
    if ($ckPartage!="O")
      $ckPartage = "N";
    list($jDeb,$mDeb,$aDeb) = explode("/",$ztDateDebut);
    if (empty($ztDateFin)) { // Si la date de fin n'est pas renseignee -> on prend la date de debut
      $ztDateFin = $ztDateDebut;
    }
    list($jFin,$mFin,$aFin) = explode("/",$ztDateFin);
    $eventOK = false;
    if (checkdate($mDeb,$jDeb,$aDeb) && checkdate($mFin,$jFin,$aFin) && !empty($ztLibelle)) {
      $ztDateDebut = $aDeb."-".$mDeb."-".$jDeb;
      $ztDateFin = $aFin."-".$mFin."-".$jFin;
      $openEvtAnnee = $aDeb;
      $eventOK = true;
    } else {
      $msg=16;
    }
  }
  if ($ztAction == "INSERT" && $eventOK) {
    $sd = $ztDateDebut;
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}evenement (eve_date_debut,eve_date_fin,eve_libelle,eve_type,eve_couleur,eve_util_id,eve_partage) VALUES ('".$ztDateDebut."','".$ztDateFin."','".$ztLibelle."',".$rdType.",'".$ztCouleur."',".$idUser.",'".$ckPartage."');");
    $msg=18;
  }

  elseif ($ztAction == "UPDATE" && $idEvt && $eventOK) {
    $sd = $ztDateDebut;
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}evenement  SET eve_date_debut='".$ztDateDebut."', eve_date_fin='".$ztDateFin."', eve_libelle='".$ztLibelle."', eve_type=".$rdType.", eve_couleur='".$ztCouleur."', eve_partage='".$ckPartage."' WHERE eve_id=".$idEvt.(($MODIF_PARTAGE) ? "" : " AND eve_util_id=".$idUser));
    $msg=19;
  }

  elseif ($ztAction == "DELETE" && $idEvt) {
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}evenement WHERE eve_id=".$idEvt.(($MODIF_PARTAGE) ? "" : " AND eve_util_id=".$idUser));
    $msg=20;
  }
  $sTmp .= "&tcType="._TYPE_EVENEMENT."&openEvtAnnee=".$openEvtAnnee;
}


/*--------------------------------------------
               GESTION DES MEMOS
--------------------------------------------*/
elseif ($ztFrom == "memo") {
  $sTmp .= "&tcType="._TYPE_MEMO;
  if ($ckPartage != "O")
    $ckPartage = "N";
  if ($ztAction == "INSERT" && !empty($ztTitre)) {
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}memo (mem_titre, mem_contenu, mem_util_id, mem_partage) VALUES ('".$ztTitre."','".$ztContenu."',".$zlUtilisateur.",'".$ckPartage."')");
  }

  elseif ($ztAction == "UPDATE" && $id && !empty($ztTitre)) {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}memo SET mem_titre='".$ztTitre."', mem_contenu='".$ztContenu."', mem_partage='".$ckPartage."' WHERE mem_id=".$id.(($MODIF_PARTAGE) ? "" : " AND mem_util_id=".$idUser));
  }

  elseif ($ztAction == "DELETE" && $id) {
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}memo WHERE mem_id=".$id.(($MODIF_PARTAGE) ? "" : " AND mem_util_id=".$idUser));
  }
}


/*--------------------------------------------
              GESTION DES LIBELLES
--------------------------------------------*/
elseif ($ztFrom == "libelles") {
  $sTmp .= "&tcType="._TYPE_LIBELLE;
  if ($ckPartage != "O")
    $ckPartage = "N";
  if ($ckJournee == "1")
    $zlDuree = "0";
  if ($ztAction == "INSERT" && !empty($ztLibelle)) {
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}libelle (lib_nom,lib_duree,lib_couleur,lib_util_id, lib_partage, lib_detail) VALUES ('".$ztLibelle."',".$zlDuree.",'".$zlCouleur."',".$idUser.",'".$ckPartage."','".$ztDetail."')");
  } elseif ($ztAction == "UPDATE" && $id && !empty($ztLibelle)) {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}libelle SET lib_nom='".$ztLibelle."',lib_duree=".$zlDuree.",lib_couleur='".$zlCouleur."',lib_partage='".$ckPartage."',lib_detail='".$ztDetail."' WHERE lib_id=".$id.(($MODIF_PARTAGE) ? "" : " AND lib_util_id=".$idUser));
  } elseif ($ztAction == "DELETE" && $id) {
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}libelle WHERE lib_id=".$id.(($MODIF_PARTAGE) ? "" : " AND lib_util_id=".$idUser));
  }
}


/*--------------------------------------------
              GESTION DES FAVORIS
--------------------------------------------*/
elseif ($ztFrom == "favoris") {
  $zlGroupe += 0;
  if ($ckPartage!="O")
    $ckPartage = "N";
  if ($ztAction == "INSERT") {
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}favoris (fav_nom, fav_url, fav_commentaire, fav_util_id, fav_fgr_id, fav_partage) VALUES ('".$ztNom."','".$ztURL."','".$ztCommentaire."',".$idUser.",".$zlGroupe.",'".$ckPartage."')");
    $openFavGrp = $zlGroupe;
  } elseif ($ztAction == "UPDATE" && $id) {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}favoris SET fav_nom='".$ztNom."', fav_url='".$ztURL."', fav_commentaire='".$ztCommentaire."', fav_fgr_id=".$zlGroupe.", fav_partage='".$ckPartage."' WHERE fav_id=".$id.(($MODIF_PARTAGE) ? "" : " AND fav_util_id=".$idUser));
    $openFavGrp = $zlGroupe;
  } elseif ($ztAction == "DELETE" && $id) {
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}favoris WHERE fav_id=".$id.(($MODIF_PARTAGE) ? "" : " AND fav_util_id=".$idUser));
  }
  $sTmp .= "&tcType="._TYPE_FAVORIS."&openFavGrp=".$openFavGrp;
}


/*--------------------------------------------
            GESTION DES ACHEVEMENTS
--------------------------------------------*/
elseif ($ztAction == "TERMINE" && $idAge) {
  $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}agenda_concerne SET aco_termine= 1-aco_termine WHERE aco_age_id=".$idAge." AND aco_util_id=".$USER_SUBSTITUE);

  if ($comp == 1) {
    Header("location: blank.html");
    exit;
  } else {
    $DB_CX->DbQuery("SELECT age_date FROM ${PREFIX_TABLE}agenda WHERE age_id=".$idAge);
    $sd = $DB_CX->DbResult(0,0);
  }
}


/*--------------------------------------------
               GESTION DU PROFIL
--------------------------------------------*/
elseif ($ztFrom == "profil" && $ztAction == "UPDATE") {
  // Recuperation des Saisies
  if ($rdTelephone!="N")
    $rdTelephone = "O";
  $zlPlanning += 0;
  if (($droit_PROFILS >= _DROIT_PROFIL_AUTRE_PARAM_PARTAGE) or (($droit_PROFILS >= _DROIT_PROFIL_PARAM_PARTAGE) and ($idUser==$USER_SUBSTITUE))) {
    if ($rdPartage=="2" && empty($ztPartage) && empty($ztPrtGroupe))
      $rdPartage = "0";
    $rdPartage += 0;
    if (($zlAffectation=="2" && empty($ztPartage) && empty($ztPrtGroupe)) || ($zlAffectation=="3" && empty($ztAffecte) && empty($ztAffGroupe)))
      $zlAffectation = "0";
    $zlAffectation += 0;
  }
  if ($ckAlertEmail!="O")
    $ckAlertEmail = "N";
  elseif ($ztEmail=="" || !$zlAffectation)
    $ckAlertEmail="N";
  if ($zlPrecision!="2")
    $zlPrecision = "1";
  $SEMAINE_TYPE= "";
  for ($i=1; $i<8; $i++)
    $SEMAINE_TYPE .= ${"bt".$i} + 0;
  if ($rdRappel != 2) {
    $zlRappelDelai = 0;
    $zlRappelType  = 1;
    $ckRappelEmail = 0;
  } elseif ($ckRappelEmail != 1)
    $ckRappelEmail = 0;
  if ($zlFormatNom!="1")
    $zlFormatNom = "0";
  if ($zlMenuDispo!="9")
    $zlMenuDispo = "8";
  if ($ztCodeURL=="")
    $ztCodeURL = md5(uniqid(rand()));
  if ($rdBarree!="N")
    $rdBarree = "O";
  if ($rdRappelAnniv != 2) {
    $zlRappelAnniv = 0;
    $zlRappelAnnivCoeff = 1440;
    $ckAnnivEmail = 0;
  } elseif ($ckAnnivEmail != 1)
    $ckAnnivEmail = 0;
  if ($ckFuseauPartage!="O")
    $ckFuseauPartage = "N";
  if ($zlFCKE!="O")
    $zlFCKE = "N";

  // Verifie si le login choisi n'est pas deja utilise
  $DB_CX->DbQuery("SELECT util_id FROM ${PREFIX_TABLE}utilisateur WHERE util_login='".$ztLogin."' AND util_id!=".$USER_SUBSTITUE);
  if (!$DB_CX->DbNumRows()) {
    $passOK = true;
    if (!empty($ztPasswdMD5)) {
      // Verification de l'ancien mot de passe
      $DB_CX->DbQuery("SELECT util_passwd FROM ${PREFIX_TABLE}utilisateur WHERE util_id=".$USER_SUBSTITUE);
      $verif_pwd = $DB_CX->DbResult(0,0);
      if ($ztOldPasswdMD5 != $verif_pwd) {
        // Mot de passe invalide
        $passOK = false;
        $tcMenu = _MENU_PROFIL;
        $msg = 4;
      } else
        $sqlPasswd = ", util_passwd='".$ztPasswdMD5."'";
    } elseif ($COOKIE_AUTH) {
      // On recupere le mot de passe dans le cookie
      if (!empty($_COOKIE) && isset($_COOKIE[$COOKIE_NOM]))
        $tabLog = explode(":",$_COOKIE[$COOKIE_NOM]);
      elseif (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS[$COOKIE_NOM]))
        $tabLog = explode(":",$HTTP_COOKIE_VARS[$COOKIE_NOM]);
      $ztPasswdMD5 = (get_magic_quotes_gpc()) ? stripslashes($tabLog[1]) : $tabLog[1];
    }
    if ($passOK) {
      if (($droit_PROFILS >= _DROIT_PROFIL_AUTRE_PARAM_PARTAGE) or (($droit_PROFILS >= _DROIT_PROFIL_PARAM_PARTAGE) and ($idUser==$USER_SUBSTITUE))) {
      // Partage du planning en consultation
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_partage WHERE ppl_util_id=".$USER_SUBSTITUE);
      if ($rdPartage==2) {// Si partage selectif uniquement
        $tabPartage = explode("+", $ztPartage);
        for ($i=0;$i<count($tabPartage);$i++) {
          if ($tabPartage[$i]!="0") {
            $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_partage VALUES ('".$USER_SUBSTITUE."','".$tabPartage[$i]."','0')");
          }
        }

        $tabPrtPartage = explode("+", $ztPrtGroupe);
        for ($ij=0;$ij<count($tabPrtPartage);$ij++) {
          list ($grpg, $ztPrtGroupe) = explode ('|', $tabPrtPartage[$ij]);
          $PrtGroupe = explode(",", $ztPrtGroupe);
          for ($i=0;$i<count($PrtGroupe);$i++) {
            if ($PrtGroupe[$i]!="0") $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_partage VALUES ('".$USER_SUBSTITUE."','".$PrtGroupe[$i]."','".$grpg."')");
          }
        }
      }

      // Partage du planning en modification
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_affecte WHERE paf_util_id=".$USER_SUBSTITUE);
      if ($zlAffectation==3) {// Si affectation selective uniquement
        $tabAffecte = explode("+", $ztAffecte);
        for ($i=0;$i<count($tabAffecte);$i++)
          if ($tabAffecte[$i]!="0") $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$USER_SUBSTITUE."','".$tabAffecte[$i]."','0')");
        $tabAffPartage = explode("+", $ztAffGroupe);
        for ($ij=0;$ij<count($tabAffPartage);$ij++) {
          list ($grpg, $ztAffGroupe) = explode ('|', $tabAffPartage[$ij]);
          $AffGroupe = explode(",", $ztAffGroupe);
          for ($i=0;$i<count($AffGroupe);$i++) {
            if ($AffGroupe[$i]!="0") $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$USER_SUBSTITUE."','".$AffGroupe[$i]."','".$grpg."')");
          }
        }
      } elseif ($zlAffectation==2) {// Si consultation basee sur la liste du partage
        if ($rdPartage!=2)
          $zlAffectation=$rdPartage;
        else {
          for ($i=0;$i<count($tabPartage);$i++)
            if ($tabPartage[$i]!="0") {
              $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$USER_SUBSTITUE."','".$tabPartage[$i]."','0')");
            }
            for ($ij=0;$ij<count($tabPrtPartage);$ij++) {
              list ($grpg, $ztPrtGroupe) = explode ('|', $tabPrtPartage[$ij]);
              $PrtGroupe = explode(",", $ztPrtGroupe);
              for ($i=0;$i<count($PrtGroupe);$i++) {
                if ($PrtGroupe[$i]!="0") $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$USER_SUBSTITUE."','".$PrtGroupe[$i]."','".$grpg."')");
              }
            }
          }
        }
      }
      // Verifie si le code pour l'export URL n'est pas deja utilise
      $DB_CX->DbQuery("SELECT util_id FROM ${PREFIX_TABLE}utilisateur WHERE util_url_export='".$ztCodeURL."' AND util_id!=".$USER_SUBSTITUE);
      if (!$DB_CX->DbNumRows()) {
        $sql = "UPDATE ${PREFIX_TABLE}utilisateur SET";
        $sql .= " util_nom='".(($AUTO_UPPERCASE == true) ? strtoupper($ztNom) : ucfirst(strtolower($ztNom)))."',";
        $sql .= " util_prenom='".ucfirst($ztPrenom)."',";
        $sql .= " util_login='".$ztLogin."',";
        $sql .= " util_interface='".$zlInterface."',";
        $sql .= " util_debut_journee='".$zlHeureDebut."',";
        $sql .= " util_fin_journee='".$zlHeureFin."',";
        $sql .= " util_telephone_vf='".$rdTelephone."',";
        $sql .= " util_planning=".$zlPlanning.",";
        if (($droit_PROFILS >= _DROIT_PROFIL_AUTRE_PARAM_PARTAGE) or (($droit_PROFILS >= _DROIT_PROFIL_PARAM_PARTAGE) and ($idUser==$USER_SUBSTITUE))){
          $sql .= " util_partage_planning='".$rdPartage."',";
        }
        $sql .= " util_email=LOWER('".$ztEmail."'),";
        if (($droit_PROFILS >= _DROIT_PROFIL_AUTRE_PARAM_PARTAGE) or (($droit_PROFILS >= _DROIT_PROFIL_PARAM_PARTAGE) and ($idUser==$USER_SUBSTITUE))){
          $sql .= " util_autorise_affect='".$zlAffectation."',";
        }
        $sql .= " util_alert_affect='".$ckAlertEmail."',";
        $sql .= " util_precision_planning='".$zlPrecision."',";
        $sql .= " util_semaine_type='".$SEMAINE_TYPE."',";
        $sql .= " util_duree_note='".$zlDureeNote."',";
        $sql .= " util_rappel_delai=".$zlRappelDelai.",";
        $sql .= " util_rappel_type=".$zlRappelType.",";
        $sql .= " util_rappel_email=".$ckRappelEmail.",";
        $sql .= " util_format_nom='".$zlFormatNom."',";
        $sql .= " util_menu_dispo='".$zlMenuDispo."',";
        $sql .= " util_url_export='".$ztCodeURL."',";
        $sql .= " util_note_barree='".$rdBarree."',";
        $sql .= " util_rappel_anniv=".$zlRappelAnniv.",";
        $sql .= " util_rappel_anniv_coeff=".$zlRappelAnnivCoeff.",";
        $sql .= " util_rappel_anniv_email=".$ckAnnivEmail.",";
        $sql .= " util_langue='".$zlLangue."',";
        $sql .= " util_fcke='".$zlFCKE."',";
        $sql .= " util_fcke_toolbar='".$zlFCKEbar."',";
        $sql .= " util_timezone='".$zlFuseauHoraire."',";
        $sql .= " util_timezone_partage='".$ckFuseauPartage."',";
        $sql .= " util_format_heure='".$zlFormatHeure."'";
        $sql .= $sqlPasswd." WHERE util_id=".$USER_SUBSTITUE;
        $DB_CX->DbQuery($sql);

        if ($droit_PROFILS >= _DROIT_PROFIL_COMPLET or $idAdmin!=0) {
          if ($droit_Aff_Login!="1")
            $droit_Aff_Login = "0";
          if ($droit_Aff_MDP!="1")
            $droit_Aff_MDP = "0";
          if ($droit_Aff_THEME!="1")
            $droit_Aff_THEME = "0";
          $droit_Aff= $droit_Aff_Login.$droit_Aff_MDP.$droit_Aff_THEME;
          $sql = "UPDATE ${PREFIX_TABLE}droit SET";
          $sql .= " droit_profils=".$zlAMProfils.",";
          $sql .= " droit_agendas=".$zlAMAgendas.",";
          $sql .= " droit_notes=".$zlAMNotes.",";
          $sql .= " droit_aff='".$droit_Aff."'";
          $sql .= " WHERE droit_util_id=".$USER_SUBSTITUE;
          $DB_CX->DbQuery($sql);
        }
        // MAJ de la semaine type de l'utilisateur dans la table des sessions
        $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_semaine_type='".$SEMAINE_TYPE."' WHERE sid_id='".$sid."'");
        $tcMenu = $tcPlg;
        $msg = 7;
        // MAJ du cookie d'identification
        if (($COOKIE_AUTH) && ($idUser == $USER_SUBSTITUE))
          setcookie($COOKIE_NOM, $ztLogin.":".$ztPasswdMD5.":".$tabLog[2].":".$hdScreen, time()+86400*$COOKIE_DUREE, "/", "", 0);

        if ($zlFuseauHoraireValid=="OUI") {
          // Timezone d'origine
          $tzLibelle=$zlFuseauHoraireORG;
          $DB_CX->DbQuery("SELECT tzn_gmt, tzn_date_ete, tzn_heure_ete, tzn_date_hiver, tzn_heure_hiver FROM ${PREFIX_TABLE}timezone WHERE tzn_zone='".$tzLibelle."'");
          $tzOrgGmt = $DB_CX->DbResult(0,0);
          $tzOrgEte = calculBasculeDST($DB_CX->DbResult(0,1),gmdate("Y"),$DB_CX->DbResult(0,2),$tzChGmt,0);
          $tzOrgHiver = calculBasculeDST($DB_CX->DbResult(0,3),gmdate("Y"),$DB_CX->DbResult(0,3),$tzChGmt,1);
          // Timezone desire
          $tzLibelle=$zlFuseauHoraire;
          $DB_CX->DbQuery("SELECT tzn_gmt, tzn_date_ete, tzn_heure_ete, tzn_date_hiver, tzn_heure_hiver FROM ${PREFIX_TABLE}timezone WHERE tzn_zone='".$tzLibelle."'");
          $tzChGmt = $DB_CX->DbResult(0,0);
          $tzChEte = calculBasculeDST($DB_CX->DbResult(0,1),gmdate("Y"),$DB_CX->DbResult(0,2),$tzChGmt,0);
          $tzChHiver = calculBasculeDST($DB_CX->DbResult(0,3),gmdate("Y"),$DB_CX->DbResult(0,3),$tzChGmt,1);

          // Calcul du decalage et mise a jour des notes
          $DB_CX->DbQuery("SELECT age_id, age_aty_id, age_date, age_heure_debut, age_heure_fin, age_date_creation FROM ${PREFIX_TABLE}agenda WHERE age_util_id=".$USER_SUBSTITUE);
          while ($enr = $DB_CX->DbNextRow()) {
            $tabDate = explode("-",$enr['age_date']);
            $decalageHoraireDOrg = calculDecalageH($tzOrgGmt,$tzOrgEte,$tzOrgHiver,mktime(floor($enr['age_heure_debut']),($enr['age_heure_debut']*60)%60,0,$tabDate[1],$tabDate[2],$tabDate[0]));
            $decalageHoraireFOrg = calculDecalageH($tzOrgGmt,$tzOrgEte,$tzOrgHiver,mktime(floor($enr['age_heure_fin']),($enr['age_heure_fin']*60)%60,0,$tabDate[1],$tabDate[2],$tabDate[0]));
            $decalageHoraireDCh = calculDecalageH($tzChGmt,$tzChEte,$tzChHiver,mktime(floor($enr['age_heure_debut']),($enr['age_heure_debut']*60)%60,0,$tabDate[1],$tabDate[2],$tabDate[0]));
            $decalageHoraireFCh = calculDecalageH($tzChGmt,$tzChEte,$tzChHiver,mktime(floor($enr['age_heure_fin']),($enr['age_heure_fin']*60)%60,0,$tabDate[1],$tabDate[2],$tabDate[0]));
            $enr['age_heure_debut'] += ($decalageHoraireDOrg-$decalageHoraireDCh);
            $enr['age_heure_fin'] +=  ($decalageHoraireFOrg-$decalageHoraireFCh);
            // on normalise la date et l'heure de debut
            if ($enr['age_heure_debut'] < 0) {
              $enr['age_heure_debut'] += 24;
              $enr['age_date'] = date("Y-m-d",mktime(12,0,0,$tabDate[1],$tabDate[2]-1,$tabDate[0]));
            }
            if ($enr['age_heure_debut'] >= 24) {
              $enr['age_heure_debut'] -= 24;
              $enr['age_date'] = date("Y-m-d",mktime(12,0,0,$tabDate[1],$tabDate[2]+1,$tabDate[0]));
            }
            // on normalise l'heure de fin
            if ($enr['age_heure_fin'] <= 0) $enr['age_heure_fin'] += 24;
            if ($enr['age_heure_fin'] > 24) $enr['age_heure_fin'] -= 24;
            // on s'occupe de l'heure de creation
            $ageDateCrt = explode(" ",$enr['age_date_creation']);
            if ($ageDateCrt[1]!="00:00:00") {
              $dtCrt = explode("-",$ageDateCrt[0]);
              $hrCrt = explode(":",$ageDateCrt[1]);
              $decalageHoraireCOrg = calculDecalageH($tzOrgGmt,$tzOrgEte,$tzOrgHiver,mktime($hrCrt[0],$hrCrt[1],$hrCrt[2],$dtCrt[1],$dtCrt[2],$dtCrt[0]));
              $decalageHoraireCCh = calculDecalageH($tzChGmt,$tzChEte,$tzChHiver,mktime($hrCrt[0],$hrCrt[1],$hrCrt[2],$dtCrt[1],$dtCrt[2],$dtCrt[0]));
              $enr['age_date_creation'] = date("Y-m-d H:i:s",mktime($hrCrt[0]+floor($decalageHoraireCOrg-$decalageHoraireCCh),$hrCrt[1]+(($decalageHoraireCOrg-$decalageHoraireCCh)*60)%60,$hrCrt[2],$dtCrt[1],$dtCrt[2],$dtCrt[0]));
            }
            if ($enr['age_aty_id']!=1) {
              @mysql_query("UPDATE ${PREFIX_TABLE}agenda SET age_date='".$enr['age_date']."', age_heure_debut=".$enr['age_heure_debut'].", age_heure_fin=".$enr['age_heure_fin'].", age_date_creation='".$enr['age_date_creation']."' WHERE age_id=".$enr['age_id']);
            } else {
              @mysql_query("UPDATE ${PREFIX_TABLE}agenda SET age_date_creation='".$enr['age_date_creation']."' WHERE age_id=".$enr['age_id']);
            }
          }
        }
      } else {
        // Code export URL deja utilise
        $tcMenu = _MENU_PROFIL;
        $msg = 17;
      }
    }
  } else {
    // Login deja utilise
    $tcMenu = _MENU_PROFIL;
    $msg = 3;
  }
}


/*--------------------------------------------
          SUBSTITUTION D'UTILISATEUR
--------------------------------------------*/
elseif ($ztAction == "SUBST" && $suid) {
  $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_util_subst_id=".$suid." WHERE sid_id='".$sid."'");
  // Si on accede a la substitution depuis la page des disponibilites, on redirige vers le planning quotidien
  if ($tcMenu==_MENU_DISP_QUOT)
    $tcMenu=_MENU_PLG_QUOT;
}


/*--------------------------------------------
              GESTION DES GROUPES
--------------------------------------------*/
elseif ($ztActionGrp == "SauvPref") {
  // Renseignement du type de groupe
  if ($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) {
    // Si l'on vient d'un planning global
    $typeGroupe = "0";
  } elseif ($tcMenu==_MENU_DISP_HEBDO || $tcMenu==_MENU_DISP_QUOT) {
    // Si l'on vient des disponibilites
    $typeGroupe = "1";
  }
  if (empty($sChoix)) {
    $sChoix = "0";
  }
  // Recuperation du groupe selectionne dans la liste ggr
  list ($grpID, $grpChoix) = explode ('|', $ggr);
  // Si la selection il n'y a pas d'identifiant de groupe renseigne
  if (!$grpID) {
    // On recherche s'il existe un groupe 'NoGroup' en base
    $DB_CX->DbQuery("SELECT ggr_id FROM ${PREFIX_TABLE}global_groupe WHERE ggr_nom='NoGroup' and ggr_util_id=".$idUser." AND ggr_type=".$typeGroupe);
    if ($DB_CX->DbNumRows()) {
      // Si OUI on recupere son identifiant
      $grpID = $DB_CX->DbResult(0,0);
    } else {
      // Si NON on le cree et on recupere son identifiant
      $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}global_groupe (ggr_util_id,ggr_nom,ggr_liste,ggr_aff,ggr_type) VALUES (".$idUser.",'NoGroup','".$sChoix."','O',".$typeGroupe.")");
      $grpID = $DB_CX->DbInsertID();
    }
  }
  // On met a jour le groupe (existant ou cree) avec la liste d'utilisateur selectionnee et on active son affichage
  $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}global_groupe SET ggr_liste='".$sChoix."', ggr_aff='O' WHERE ggr_id=".$grpID." AND ggr_type=".$typeGroupe);
  // On desactive l'affichage des autres groupes de l'utilisateur
  $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}global_groupe SET ggr_aff='N' WHERE ggr_id!=".$grpID." AND ggr_util_id=".$idUser." AND ggr_type=".$typeGroupe);

  // Enregistrement des options d'affichage
  // Precision / Heure debut / Heure fin
  $zlPrec+=0;
  $zlHD+=0;
  $zlHF+=0;
  // S'il existe des informations de precisions d'affichage, on les met a jour (si applicable)
  $infoPrecision = ($zlPrec) ? ", aff_precision='".$zlPrec."', aff_debut=".$zlHD.", aff_fin=".$zlHF : "";
  // Choix 'Figer la vue'
  if ($ckAffGr!="O")
    $ckAffGr="N";
  // Choix 'Afficher non consultable ou non affectable' selon le cas
  if ($ckAffCache!="O")
    $ckAffCache="N";
  // Recherche s'il existe deja des preferences d'affichage pour cet utilisateur
  $DB_CX->DbQuery("SELECT aff_util_id FROM ${PREFIX_TABLE}planning_affichage WHERE aff_util_id=".$idUser." AND aff_type=".$typeGroupe);
  if ($DB_CX->DbNumRows()) {
    // Si OUI on les met a jour
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}planning_affichage SET aff_figer='".$ckAffGr."', aff_user='".$ckAffCache."'".$infoPrecision." WHERE aff_util_id=".$idUser." AND aff_type=".$typeGroupe);
  } else {
    // Si NON on les cree
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affichage (aff_util_id,aff_type,aff_figer,aff_user,aff_precision,aff_debut,aff_fin) VALUES (".$idUser.",".$typeGroupe.",'".$ckAffGr."','".$ckAffCache."','".$zlPrec."',".$zlHD.",".$zlHF.")");
  }

  $msg = 21;
  $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg;
  $RetConsul = true;
}

elseif ($ztActionGrp == "SauvGrp") {
  if ($utilgr!="O") {
    list ($grpg, $GrChoix) = explode ('|', $ggr);
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}global_groupe SET ggr_liste='".$sChoix."' WHERE ggr_id=".$grpg."");
    $msg = 21;
    $url="&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr";
    $RetConsul=true;
  } else {
    list ($grpg, $GrChoix) = explode ('|', $ggr);
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}groupe_util SET gr_util_liste='".$sChoix."' WHERE gr_util_id=".$grpg."");

    $tChoix= explode (',', $sChoix);
    $TabPartage= array();
    $DB_CX->DbQuery("SELECT DISTINCT ppl_util_id FROM ${PREFIX_TABLE}planning_partage WHERE ppl_gr=".$grpg."");
    while ($enr = $DB_CX->DbNextRow()) {
      $TabPartage[]=$enr['ppl_util_id'];
    }
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_partage WHERE ppl_gr=".$grpg."");
    for ($j=0; $j<count($TabPartage); $j++) {
      for ($i=0; $i<count($tChoix); $i++) {
        $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_partage VALUES ('".$TabPartage[$j]."','".$tChoix[$i]."','".$grpg."')");
      }
    }
    $TabAffecte= array();
    $DB_CX->DbQuery("SELECT DISTINCT paf_util_id FROM ${PREFIX_TABLE}planning_affecte WHERE paf_gr=".$grpg."");
    while ($enr = $DB_CX->DbNextRow()) {
      $TabAffecte[]=$enr['paf_util_id'];
    }
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_affecte WHERE paf_gr=".$grpg."");
    for ($j=0; $j<count($TabAffecte); $j++) {
      for ($i=0; $i<count($tChoix); $i++) {
        $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$TabAffecte[$j]."','".$tChoix[$i]."','".$grpg."')");
      }
    }
    $msg = 21;
    $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr&groupe=1";
    $RetConsul = true;
  }
}

elseif ($ztActionGrp == "SupGrp") {
  if ($utilgr!="O") {
    list ($grpg, $GrChoix) = explode ('|', $ggr);
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}global_groupe WHERE ggr_id=".$grpg."");
    $msg = 21;
    $url="&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg;
    $RetConsul=true;
  } else {
    list ($grpg, $GrChoix) = explode ('|', $ggr);
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}groupe_util WHERE gr_util_id=".$grpg."");
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_affecte WHERE paf_gr=".$grpg."");
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_partage WHERE ppl_gr=".$grpg."");
    $msg = 21;
    $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&groupe=1";
    $RetConsul = true;
  }
}

elseif ($ztActionGrp == "AjoutGgg") {
  if ($utilgr!="O") {
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}global_groupe VALUES ('','".$idUser."','".$ztNom."','".$sChoix."','N','".$typegr."')");
    $grpg = $DB_CX->DbInsertID();
    $msg = 21;
    $url="&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr";
    $RetConsul=true;
  } else {
    $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}groupe_util VALUES ('','".$ztNom."','".$sChoix."')");
    $grpg = $DB_CX->DbInsertID();
    $msg = 21;
    $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr&groupe=1&ztNom=".$ztNom;
    $RetConsul = true;
  }
}

elseif ($ztActionGrp == "ModifGgg") {
  if ($utilgr!="O") {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}global_groupe SET ggr_nom='".$ztNom."', ggr_liste='".$sChoix."' WHERE ggr_id=".$grpg."");
    $msg = 21;
    $url="&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr";
    $RetConsul=true;
  } else {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}groupe_util SET gr_util_nom='".$ztNom."', gr_util_liste='".$sChoix."' WHERE gr_util_id=".$grpg."");
    $msg = 21;
    $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr&groupe=1";
    $RetConsul = true;

    $tChoix= explode (',', $sChoix);
    $TabPartage= array();
    $DB_CX->DbQuery("SELECT DISTINCT ppl_util_id FROM ${PREFIX_TABLE}planning_partage WHERE ppl_gr=".$grpg."");
    while ($enr = $DB_CX->DbNextRow()) {
      $TabPartage[]=$enr['ppl_util_id'];
    }
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_partage WHERE ppl_gr=".$grpg."");
    for ($j=0; $j<count($TabPartage); $j++) {
      for ($i=0; $i<count($tChoix); $i++) {
        $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_partage VALUES ('".$TabPartage[$j]."','".$tChoix[$i]."','".$grpg."')");
      }
    }
    $TabAffecte= array();
    $DB_CX->DbQuery("SELECT DISTINCT paf_util_id FROM ${PREFIX_TABLE}planning_affecte WHERE paf_gr=".$grpg."");
    while ($enr = $DB_CX->DbNextRow()) {
      $TabAffecte[]=$enr['paf_util_id'];
    }
    $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}planning_affecte WHERE paf_gr=".$grpg."");
    for ($j=0; $j<count($TabAffecte); $j++) {
      for ($i=0; $i<count($tChoix); $i++) {
        $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}planning_affecte VALUES ('".$TabAffecte[$j]."','".$tChoix[$i]."','".$grpg."')");
      }
    }
    $msg = 21;
    $url = "&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&msg=".$msg."&ggr=".$grpg."|".$sChoix."&ztActionGrp=NvGr&groupe=1";
    $RetConsul = true;
  }
}


/*--------------------------------------------
    DECONNEXION DU COMPTE D'ADMINISTRATION
--------------------------------------------*/
elseif ($ztDiscon == "Admin") {
  $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_admin_id=0 WHERE sid_id='".$sid."'");
  $idAdmin = 0;
  $url = "&tcMenu=".$tcPlg;
  $RetConsul = true;
}

  // Fermeture BDD
  $DB_CX->DbDeconnect();

  $tabDate = explode("-",$sd);
  if ($tcMenu>=_MENU_DISP_HEBDO)
    $sTmp .= "&tcPlg=".$tcPlg;
  if (!$RetConsul) {
    $tsjour = mktime(12,0,0,intval($tabDate[1]),intval($tabDate[2]),$tabDate[0]);
    $url = $sTmp."&tcMenu=".(($ztAction == "UPDATE" && $RetProfil == "profil") ? _MENU_PROFIL : $tcMenu)."&sd=".$tsjour."&msg=".$msg;
    if (!empty($ggr)) {  // si on est en edition de note depuis les plannings globaux
      $url .= "&ggr=".$ggr."&ztActionGrp=".$ztActionGrp;
    }
  }
  if ($classSMTPLoaded)
    $mailer->smtp->quit();

  Header("location: agenda.php?sid=".$sid.$url);
?>
