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

/****************************************************************************************************************/
  function getInfoNote(&$enr) {
    global $classNote,$lienStatut,$lien,$styleNote,$plageNote,$libelleNote,$detailNote,$sOption,$popupFixe;
    global $USER_SUBSTITUE,$AUTORISE_SUPPR,$NOTE_BARREE,$MODIF_PARTAGE,$idUser,$AgendaFondNotePerso,$AgendaFondNote,$AgendaTexteTitrePopup,$PlanningNotePrivee,$AgendaContactPopup;
    global $formatHeure;
    global $droit_PROFILS, $droit_AGENDAS, $droit_NOTES, $AFFECTE_NOTE;
    //Recuperation des droits de l'utilisateur sur la note
    attributDroits($enr, $droitModifStatut, $droitModifNotePerso, $droitModifNoteAffectee, $droitSuppOcc, $droitSuppNoteCreee, $droitSuppNoteAffectee, $droitApprNote, $USER_SUBSTITUE, $AFFECTE_NOTE);
    //Propriete Privee ou Publique de la note
    if ($USER_SUBSTITUE!=$idUser && $enr['age_util_id']!=$idUser && $enr['age_prive']==1) {
      $enr['age_libelle'] = trad("COMMUN_OCCUPE");
      $enr['age_detail'] = "<P class=\"infoDate\">".trad("COMMUN_NOTE_PRIVEE")."</P>"; // Detail et info de creation non visible si note privee
      $enr['age_couleur'] = $PlanningNotePrivee; // Couleur de note non visible si note privee
      $enr['age_lieu'] = ""; // Emplacement non visible  si note privee
      $notePrive = true;
    } else {
      $notePrive = false;
      //Info sur la creation / modification de la note
      afficheInfoModifNote($enr, $USER_SUBSTITUE);
    }
    //Propriete Active ou Terminee de la note
    if ($enr['aco_termine'] == 1) {
      $styleNote = ($NOTE_BARREE) ? "line-through" : "none";
      $imgTemoin = "puce_ok.gif";
    } else {
      $styleNote = "none";
      $imgTemoin = "puce_ko.gif";
    }
    // Droit en modification du statut de la note
    if ($droitModifStatut && !$notePrive) {
      $lienStatut = "<A href=\"/\" onclick=\"javascript: parent.termineNote('".$enr['age_id']."',".(($NOTE_BARREE) ? "true" : "false")."); return false;\"><IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" id=\"t".$enr['age_id']."\" title=\"".trad("COMMUN_CHANGER_STATUT")."\"></A>";
    } else {
      $lienStatut = "<IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" alt=\"\">";
    }
    //Couleur de fond de la note si non definie dans la bdd
    if (empty($enr['age_couleur']))
      $enr['age_couleur'] = ($enr['age_util_id']==$USER_SUBSTITUE) ? $AgendaFondNotePerso : $AgendaFondNote;
    // Droit en modification sur une note personnelle
    if ($droitModifNotePerso) {
      $lien = " href=\"javascript: affNote('".$enr['age_id']."')\"";
      $classNote = "borderNotePerso";
    } else {
      // Droit en modification sur une note d'un planning consulte
      $lien = ($droitModifNoteAffectee && !$notePrive) ? " href=\"javascript: affNote('".$enr['age_id']."')\"" : "";
      $classNote = ($enr['age_util_id']==$USER_SUBSTITUE) ? "borderNotePerso" : "borderNote";
    }
    //Plage horaire de la note
    $plageNote = ($enr['age_aty_id']==2) ? afficheHeure(floor($enr['age_heure_debut']),$enr['age_heure_debut'],$formatHeure)."&rsaquo;".afficheHeure(floor($enr['age_heure_fin']),$enr['age_heure_fin'],$formatHeure) : trad("COMMUN_JOURNEE_ENTIERE");
    //Info a afficher dans le popup
    $libelleNote = "<A style='font-weight:normal;color:".$AgendaTexteTitrePopup."'>".htmlspecialchars($enr['age_libelle']).((!empty($enr['age_lieu'])) ? "<BR><I>(".$enr['age_lieu'].")</I>" : "")."</A>";
    $detailNote = htmlspecialchars(nlTObr($enr['age_detail']));
    // Options possibles sur la note (rappel, suppression, appropriation)
    $sOption = $popupFixe = "";
    if ($notePrive==false) {
      // Lien vers affichage FIXE du popup
      $popupFixe = "<TD align=\"right\" valign=\"top\"><A href=\"/\" onclick=\"javascript: stc('".addslashes($plageNote)."','".addslashes($libelleNote)."','".addslashes($detailNote)."','".trad("POPUP_FERMER")."'); return false;\"><IMG src=\"image/popup_open.gif\" width=\"9\" height=\"8\" title=\"".trad("QUOTIDIEN_NOTE_DETAIL")."\" border=\"0\" align=\"absmiddle\"></A></TD>";
      // Indique si un rappel a ete programme
      if ($enr['age_rappel']>0) {
        $rappel = trad("COMMUN_RAPPEL")." <B>".$enr['age_rappel'];
        if ($enr['age_rappel_coeff']==1)
          $rappel .= " ".trad("COMMUN_MINUTE");
        elseif ($enr['age_rappel_coeff']==60)
          $rappel .= " ".trad("COMMUN_HEURE");
        else
          $rappel .= " ".trad("COMMUN_JOUR");
        $rappel .= "</B> ".trad("COMMUN_AVANCE");
        $sOption .= "&nbsp;<IMG src=\"image/rappel.gif\" border=\"0\" align=\"absmiddle\"".infoPopup($rappel).">";
      }
      if ($enr['age_ape_id']!=1) {
        // Droit en suppression de l'occurence
        if ($droitSuppOcc) {
          $sOption .= "&nbsp;<A href=\"javascript: supprOcc('".$enr['age_id']."','0');\"><IMG src=\"image/recurrent.gif\" width=\"13\" height=\"11\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_SUPPR_OCCURENCE")."\"></A>";
        } else {
          $sOption .= "&nbsp;<IMG src=\"image/recurrent.gif\" width=\"13\" height=\"11\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_NOTE_RECURRENTE")."\">";
        }
      }
      // Droit en suppression d'une note creee
      if ($droitSuppNoteCreee) {
        $sOption .= "&nbsp;<A href=\"javascript: supprOcc('".(($enr['age_mere_id']) ? $enr['age_mere_id'] : $enr['age_id'])."','1');\"><IMG src=\"image/suppr.gif\" width=\"12\" height=\"12\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_SUPPR_NOTE")."\"></A>";
      }
      // Droit en suppression d'une note affectee
      elseif ($droitSuppNoteAffectee) {
        $sOption .= "&nbsp;<A href=\"javascript: supprOcc('".$enr['age_id']."','2');\"><IMG src=\"image/suppr.gif\" width=\"12\" height=\"12\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_SUPPR_NOTE")."\"></A>";
      }
      // Info du contact associe (et lien eventuel vers la fiche contact) selon les droits
      $sOption .= getInfoContactAssocie($enr,$droit_NOTES);
    }
    // Droit en appropriation d'une note affectee
    if ($droitApprNote) {
      $sOption .= "&nbsp;<A href=\"javascript: apprNote('".$enr['age_id']."');\"><IMG src=\"image/appropriation.gif\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_APPROPRIATION")."\"></A>";
    }
  }
/****************************************************************************************************************/


  $dateCrt = $anneeEnCours."-".$moisEnCours."-".$jourEnCours;
  $ligneEvent = $ligneAnniv = $premiereLettre = "";
  // Evenement(s) du jour
  $DB_CX->DbQuery("SELECT DISTINCT eve_id, eve_libelle, eve_util_id, eve_type, DATE_FORMAT(eve_date_debut,'%d/%m/%Y') AS dateDebut, DATE_FORMAT(eve_date_fin,'%d/%m/%Y') AS dateFin FROM ${PREFIX_TABLE}evenement WHERE DATE_FORMAT(eve_date_debut,'%Y%m%d')<='".date("Ymd",$sd)."' AND DATE_FORMAT(eve_date_fin,'%Y%m%d')>='".date("Ymd",$sd)."'".(($USER_SUBSTITUE==$idUser) ? " AND (eve_util_id=".$idUser." OR eve_partage='O')" : " AND eve_partage='O'"));
  while ($enr = $DB_CX->DbNextRow()) {
    $popupEvent = ($enr['dateDebut']!=$enr['dateFin']) ? infoPopup(sprintf(trad("COMMUN_DUREE_EVENEMENT"), $enr['dateDebut'], $enr['dateFin'])) : "";
    $ligneEvent .= (($MODIF_PARTAGE || $enr['eve_util_id']==$idUser) && ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) ? "<A class=\"sousMenu\" href=\"javascript: affEvent('".$enr['eve_id']."')\"".$popupEvent.">" : "<A class=\"sousMenu\"".$popupEvent.">";
    $ligneEvent .= "<IMG src=\"image/evenement/evenement".$enr['eve_type'].".gif\" width=\"16\" height=\"16\" alt=\"\" border=\"0\" align=\"absmiddle\" vspace=\"1\" hspace=\"1\">&nbsp;".htmlspecialchars($enr['eve_libelle'])."</A> - ";
  }
  // Anniversaire(s) de l'agenda
  $DB_CX->DbQuery("SELECT age_id,age_libelle,age_date FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND age_date LIKE '%".substr($dateCrt,4)."' AND DATE_FORMAT(age_date,'%Y%m%d')<=".date("Ymd",$sd)." AND age_aty_id=1");
  while ($enr = $DB_CX->DbNextRow()) {
    if (empty($premiereLettre))
      $premiereLettre = strtolower(substr($enr['age_libelle'],0,1));
    $infoAge = afficheAge($enr['age_date'],$sd);
    $ligneAnniv .= ($USER_SUBSTITUE==$idUser) ? "<A href=\"javascript: affAnniv('".$enr['age_id']."');\" class=\"sousMenu\"".$infoAge.">".$enr['age_libelle']."</A> / " : "<A class=\"sousMenu\"".$infoAge.">".$enr['age_libelle']."</A> / ";
  }
  // Anniversaire(s) du calepin (y compris les contacts partages)
  $DB_CX->DbQuery("SELECT cal_id,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_util_id,cal_partage,cal_date_naissance FROM ${PREFIX_TABLE}calepin WHERE (cal_util_id=".$USER_SUBSTITUE." OR cal_partage='O') AND cal_date_naissance LIKE '%".substr($dateCrt,4)."' AND DATE_FORMAT(cal_date_naissance,'%Y%m%d')<=".date("Ymd",$sd));
  while ($enr = $DB_CX->DbNextRow()) {
    if (empty($premiereLettre))
      $premiereLettre = strtolower(substr($enr['nomContact'],0,1));
    $infoAge = afficheAge($enr['cal_date_naissance'],$sd);
    $ligneAnniv .= ($enr['cal_util_id']==$idUser || ($enr['cal_partage']=='O' && $MODIF_PARTAGE)) ? "<A href=\"?ztAction=M&id=".$enr['cal_id']."&sid=".$sid."&tcMenu="._MENU_CONTACT."&tcPlg=".$tcMenu."&sd=".$sd."\" class=\"sousMenu\"".$infoAge.">".$enr['nomContact']."</A> / " : "<A class=\"sousMenu\"".$infoAge.">".$enr['nomContact']."</A> / ";
  }
  if (!empty($ligneAnniv)) {
    $genre = prefixeMot($premiereLettre,trad("COMMUN_PREFIXE_D"),trad("COMMUN_PREFIXE_DE"));
    $ligneAnniv = "<IMG src=\"image/anniversaire.gif\" width=\"16\" height=\"16\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("COMMUN_ANNIVERSAIRE")." ".$genre.$ligneAnniv;
  }
  $ligneAnniv = $ligneEvent.$ligneAnniv;
?>
<!-- MODULE PLANNING QUOTIDIEN -->
<script language="javascript">
<!--
  var oldColor = '';
  var newColor = '<?php echo $AgendaLigneHover; ?>';
  function swapColor(pLigne, pMouseOver) {
    var trLigne = document.getElementById('ligne' + pLigne);
    if (pMouseOver) {
      oldColor = trLigne.style.backgroundColor;
      trLigne.style.backgroundColor=newColor;
    } else {
      trLigne.style.backgroundColor=oldColor;
    }
  }
//-->
</script>
  <FORM action="<?php echo "?sid=".$sid."&tcMenu=".$tcMenu."&sd=".$sd; ?>" method="post">
  <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
  <TR>
    <TD width="100%" height="28" nowrap class="sousMenu"><?php echo substr($ligneAnniv,0,strlen($ligneAnniv)-3); ?></TD>
    <TD align="right" nowrap class="sousMenu" style="text-align:right;"><?php genereListeCouleur(); ?>&nbsp;&nbsp;&nbsp;<A href="javascript: parent.imprime('<?php echo $tcMenu; ?>','<?php echo $sd; ?>','<?php echo urlencode(str_replace("#","!",$FILTRE_COULEUR)); ?>');"><IMG src="image/impression.gif" width="23" height="21" border="0" align="absmiddle" title="<?php echo trad("QUOTIDIEN_IMPRIMER"); ?>"></A>&nbsp;&nbsp;</TD>
  </TR>
  </TABLE>
  </FORM>
  <BR>
  <TABLE cellspacing="0" cellpadding="0" width="99%" border="0"><TR><TD>
    <TABLE cellspacing="0" cellpadding="0" width="100%" border="0" style="border:solid 1px <?php echo $AgendaBordureTableau; ?>">
<?php
  //Si l'utilisateur a choisi une couleur de note on l'ajoute dans la clause WHERE de la recherche
  $whereCouleur = "";
  if ($FILTRE_COULEUR != "ALL" && !empty($FILTRE_COULEUR)) {
    $whereCouleur = ($FILTRE_COULEUR == $AgendaFondNotePerso) ? " AND (age_couleur='".$FILTRE_COULEUR."' OR age_couleur='')" : " AND age_couleur='".$FILTRE_COULEUR."'";
  }

  //Parametres de la journee choisis par l'utilisateur
  $DB_CX->DbQuery("SELECT t1.util_debut_journee, t1.util_fin_journee, t2.util_precision_planning FROM ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2 WHERE t1.util_id=".$USER_SUBSTITUE." AND t2.util_id=".$idUser);
  $debutJournee = $DB_CX->DbResult(0,0);
  $finJournee   = $DB_CX->DbResult(0,1);
  $precisionAff = $DB_CX->DbResult(0,2);

  //Preparation au decalage horaire
  prepareDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$moisEnCours,$jourEnCours,$anneeEnCours));

  //Heure de debut et de fin en fonction des notes non affichees
  $DB_CX->DbQuery("SELECT MIN($age_heure_debut), MAX($age_heure_fin), MAX(IF($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0,1,0)), MAX(IF($age_date='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin,1,0)) FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND ($age_date='".$dateCrt."' OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND age_aty_id=2".$whereCouleur);
  if ($DB_CX->DbResult(0,0)!=NULL) {
    $debutJournee = min($debutJournee,$DB_CX->DbResult(0,0));
    $finJournee = max($finJournee,$DB_CX->DbResult(0,1));
    if ($DB_CX->DbResult(0,2)) $debutJournee = 0;
    if ($DB_CX->DbResult(0,3)) $finJournee = 24;
  }

  if ($precisionAff==1 && (($debutJournee-floor($debutJournee)==0.25) || ($debutJournee-floor($debutJournee)==0.75)))
    $debutJournee -= 0.25;
  if ($precisionAff==1 && (($finJournee-floor($finJournee)==0.25) || ($finJournee-floor($finJournee)==0.75)))
    $finJournee += 0.25;

  $dureeJournee = ($finJournee-$debutJournee)*2*$precisionAff;

  //Nb maxi de notes en meme temps
  $maxNote = 1;
  $taIdbNoteMultiple = array();
  for ($hCrt=$debutJournee;$hCrt<$finJournee;$hCrt=$hCrt+(0.5/$precisionAff)) {
    if ($precisionAff==1)
      $DB_CX->DbQuery("SELECT DISTINCT(age_id) FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND ($age_date='".$dateCrt."' OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin>$hCrt)) AND ((($age_heure_debut<=".$hCrt." OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND ($age_heure_fin>".$hCrt." OR $age_heure_debut>=$age_heure_fin)) OR (($age_heure_debut<=".($hCrt+0.25)." OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND ($age_heure_fin>".($hCrt+0.25)." OR $age_heure_debut>=$age_heure_fin))) AND age_aty_id=2".$whereCouleur);
    else
      $DB_CX->DbQuery("SELECT DISTINCT(age_id) FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND ($age_date='".$dateCrt."' OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin>$hCrt)) AND ($age_heure_debut<=".$hCrt." OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND ($age_heure_fin>".$hCrt." OR $age_heure_debut>=$age_heure_fin) AND age_aty_id=2".$whereCouleur);
    if ($DB_CX->DbNumRows() > 1) {
      while ($enr = $DB_CX->DbNextRow()) {
        if (!in_array($enr['age_id'], $taIdbNoteMultiple))
          $taIdbNoteMultiple[] = $enr['age_id'];
      }
      if ($DB_CX->DbNumRows() > $maxNote)
        $maxNote = $DB_CX->DbNumRows();
    }
  }
  // Definit la taille du colspan qui sera applique lorsqu'il n'y a pas de chevauchement de note
  $colspanSize = $maxNote*2-1;

  $widthCell = round(100/$maxNote++);

  //Initialisation de la matrice d'affichage
  $celluleIntermediaire = "      <TD{lienAjout}><IMG src=\"image/trans.gif\" width=2></TD>\n";
  for ($i=0;$i<$dureeJournee;$i++) {
    for ($j=0;$j<$maxNote;$j++) {
      $matAff[$i][$j] = ($j) ? $celluleIntermediaire."      <TD{lienAjout}>&nbsp;</TD>\n" : "borderNone";
      $tabCol[$j] = 0;
    }
  }

  //Lecture des notes couvrant la totalite d'une journee
  $DB_CX->DbQuery("SELECT age_id,age_heure_debut,age_heure_fin,age_ape_id,age_libelle,age_detail,age_util_id,CONCAT(".$NOM_UTIL_CREATEUR.") AS nomCreateur,aco_termine,age_prive,age_couleur,age_rappel,age_rappel_coeff,age_mere_id,age_nb_participant,age_createur_id,age_aty_id,$age_date AS dateNote,age_date_creation,age_date_modif,age_modificateur_id,CONCAT(".$NOM_UTIL_MODIFICATEUR.") AS nomModificateur,age_lieu,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_id,cal_util_id,cal_partage,cal_societe,cal_adresse,cal_cp,cal_ville,cal_pays,cal_domicile,cal_travail,cal_portable,cal_fax,cal_email,cal_emailpro FROM ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2 WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND $age_date='".$dateCrt."' AND age_aty_id=3 AND t1.util_id=age_createur_id AND t2.util_id=age_modificateur_id".$whereCouleur." ORDER BY age_heure_debut ASC");
  $iGlb = 0;
  $aGlobale = array();
  while ($enr = $DB_CX->DbNextRow()) {
    //Decalage des notes en fonction du fuseau horaire
    list($enr['age_heure_debut'],$enr['age_heure_fin'],$enr['dateCreation'],$enr['dateModif']) = decaleNote($tzGmt,$tzEte,$tzHiver,$dateCrt,$enr['dateNote'],$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
    //Formatage des informations sur la note
    getInfoNote($enr);
    //Stockage des infos relatives aux notes couvrant la totalite d'une journee
    $aGlobale[$iGlb]  = "<TR><TD colspan=\"".($colspanSize+2)."\" class=\"".$classNote."\" bgcolor=\"".$enr['age_couleur']."\"><TABLE border=0 cellpadding=0 cellspacing=0 width=\"100%\"><TR height=\"15\"><TD width=\"100%\">";
    $aGlobale[$iGlb] .= $lienStatut;
    $aGlobale[$iGlb] .= "&nbsp;<A".$lien." id=\"n".$enr['age_id']."\" style=\"text-decoration: ".$styleNote.";\" onmouseover=\"javascript: dtc('".addslashes($plageNote)."','".addslashes($libelleNote)."','".addslashes($detailNote)."'); return false;\" onmouseout=\"javascript: nd(); return true;\"><B>".$enr['age_libelle'].((!empty($enr['age_lieu'])) ? " <I>(".$enr['age_lieu'].")</I>" : "")."</B></A>";
    $aGlobale[$iGlb] .= $sOption."</TD>";
    $aGlobale[$iGlb] .= $popupFixe;
    $aGlobale[$iGlb++] .= "</TR></TABLE></TD></TR>\n";
  }

  //Lecture des notes de la journee
  $DB_CX->DbQuery("SELECT age_id,age_heure_debut,age_heure_fin,age_ape_id,age_libelle,age_detail,age_util_id,CONCAT(".$NOM_UTIL_CREATEUR.") AS nomCreateur,aco_termine,age_prive,age_couleur,age_rappel,age_rappel_coeff,age_mere_id,age_nb_participant,age_createur_id,age_aty_id,$age_date AS dateNote,age_date_creation,age_date_modif,age_modificateur_id,CONCAT(".$NOM_UTIL_MODIFICATEUR.") AS nomModificateur,age_lieu,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_id,cal_util_id,cal_partage,cal_societe,cal_adresse,cal_cp,cal_ville,cal_pays,cal_domicile,cal_travail,cal_portable,cal_fax,cal_email,cal_emailpro FROM ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2 WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND ($age_date='".$dateCrt."' OR ($age_dateAvant='".$dateCrt."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND age_aty_id=2 AND t1.util_id=age_createur_id AND t2.util_id=age_modificateur_id".$whereCouleur." ORDER BY age_date, age_heure_debut ASC, age_heure_fin DESC");
  while ($enr = $DB_CX->DbNextRow()) {
    //Decalage des notes en fonction du fuseau horaire
    list($enr['age_heure_debut'],$enr['age_heure_fin'],$enr['dateCreation'],$enr['dateModif']) = decaleNote($tzGmt,$tzEte,$tzHiver,$dateCrt,$enr['dateNote'],$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
    //Formatage des informations sur la note
    getInfoNote($enr);
    //Modification des horaires de la note en fonction de la precision d'affichage de l'utilisateur
    $hDebut = ($precisionAff==1 && (($enr['age_heure_debut']-floor($enr['age_heure_debut'])==0.25) || ($enr['age_heure_debut']-floor($enr['age_heure_debut'])==0.75))) ? $enr['age_heure_debut']-0.25 : $enr['age_heure_debut'];
    $iMat = floor(($enr['age_heure_debut']-$debutJournee)*2*$precisionAff);
    $hFin = ($precisionAff==1 && (($enr['age_heure_fin']-floor($enr['age_heure_fin'])==0.25) || ($enr['age_heure_fin']-floor($enr['age_heure_fin'])==0.75))) ? $enr['age_heure_fin']+0.25 : $enr['age_heure_fin'];
    $hFin = min($hFin,$finJournee);
    $duree = ($hFin-$hDebut)*2*$precisionAff;

    //Position dans la matrice d'affichage
    $colToUse = 0;
    for ($i=1;$i<$maxNote && !$colToUse;$i++) {
      if ($tabCol[$i]<=$hDebut) {
        $colToUse = $i;
        $tabCol[$i] = $hFin;
      }
    }

    //Stockage des informations sur la note
    if (in_array($enr['age_id'],$taIdbNoteMultiple)) {
      $colspanCell = " width=\"".$widthCell."%\"";
    } else {
      $colspanCell = " colspan=\"".$colspanSize."\" width=\"".($widthCell * ($maxNote-1))."%\"";
      // Effacement des cellules adjascentes en cas de non chevauchement
      for ($i=0;$i<$duree;$i++) {
        for ($j=2;$j<$maxNote;$j++) {
          $matAff[$iMat+$i][$j] = "";
        }
      }
    }
    $matAff[$iMat][$colToUse]  = $celluleIntermediaire."      <TD".$colspanCell." valign=\"top\" rowspan=\"".$duree."\" class=\"".$classNote."\" bgcolor=\"".$enr['age_couleur']."\"><TABLE border=0 cellpadding=0 cellspacing=0 width=\"100%\"><TR><TD width=\"100%\" valign=\"top\">";
    $matAff[$iMat][$colToUse] .= $lienStatut;
    $matAff[$iMat][$colToUse] .= "&nbsp;<A".$lien." id=\"n".$enr['age_id']."\" style=\"text-decoration: ".$styleNote.";\" onmouseover=\"javascript: dtc('".addslashes($plageNote)."','".addslashes($libelleNote)."','".addslashes($detailNote)."'); return false;\" onmouseout=\"javascript: nd(); return true;\"><B>".$enr['age_libelle'].((!empty($enr['age_lieu'])) ? " <I>(".$enr['age_lieu'].")</I>" : "")."</B></A>";
    $matAff[$iMat][$colToUse] .= $sOption."</TD>";
    $matAff[$iMat][$colToUse] .= $popupFixe;
    $matAff[$iMat][$colToUse] .= "</TR></TABLE></TD>\n";

    //Correction de l'affichage pour les notes sur plusieurs lignes
    if ($matAff[$iMat][0]=="borderNone") {
      $matAff[$iMat][0] = ($duree == 1) ? "borderAll" : "borderTop";
    }
    elseif ($matAff[$iMat][0]=="borderBottom" && $duree > 1) {
      $matAff[$iMat][0] = "borderMiddle";
    }
    for ($i=1;$i<$duree-1;$i++) {
      if ($matAff[$iMat+$i][0]!="borderMiddle") {
        $matAff[$iMat+$i][0] = "borderMiddle";
      }
      $matAff[$iMat+$i][$colToUse] = $celluleIntermediaire;
    }
    if ($duree>1) {
      if ($matAff[$iMat+$duree-1][0] == "borderNone")
        $matAff[$iMat+$duree-1][0] = "borderBottom";
      $matAff[$iMat+$duree-1][$colToUse] = $celluleIntermediaire;
    }
  }

  //Affichage des notes couvrant toute la journee
  for ($i=0;$i<count($aGlobale);$i++)
    echo $aGlobale[$i];
  //Affichage du tableau
  $index = 0;
  for ($i=0;$i<$dureeJournee;$i++) {
    $index=1-$index;
    if ((($USER_SUBSTITUE==$idUser || $AFFECTE_NOTE) and ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) or ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION)) {
      $lienAjout =  " style=\"cursor:pointer;\" onmouseover=\"javascript: swapColor('".$i."',true);\" onmouseout=\"javascript: swapColor('".$i."',false);\" onclick=\"javascript: nvType('"._TYPE_NOTE."','".($debutJournee+($i/(2*$precisionAff)))."');\" title=\"".trad("QUOTIDIEN_AJOUT_NOTE_H")."\"";
    } else {
      $lienAjout = "";
    }
    echo "    <TR style=\"background:".$bgColor[$index]."\" id=\"ligne".$i."\" height=\"19\">\n";
    for ($j=0;$j<$maxNote+1;$j++) {
      if ($j) {
        echo str_replace("{lienAjout}",$lienAjout,$matAff[$i][$j]);
      } else {
        echo "      <TD width=\"40\" nowrap class=\"".$matAff[$i][0]."\"".$lienAjout.">".afficheHeure($debutJournee+($i/(2*$precisionAff)),$debutJournee+($i/(2*$precisionAff)),$formatHeure)."</TD>\n";
      }
    }
    echo "    </TR>\n";
  }
?>
    </TABLE>
  </TD></TR></TABLE>
  <DIV class="timezone"><?php echo sprintf(trad("COMMUN_FUSEAU_ACTUEL"), (($tzGmt<0) ? "-" : "+").afficheHeure(floor(abs($tzGmt)),abs($tzGmt)), $tzLibelle); ?></DIV>
<!-- FIN MODULE PLANNING QUOTIDIEN -->
