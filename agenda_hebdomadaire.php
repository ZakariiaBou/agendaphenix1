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

  $lienAvant = mktime(12,0,0,$moisEnCours,$premierJourSemaine-7,$anneeEnCours);
  $lienApres = mktime(12,0,0,$moisEnCours,$premierJourSemaine+7,$anneeEnCours);

  //Parametres de la journee choisis par l'utilisateur
  $DB_CX->DbQuery("SELECT t1.util_debut_journee, t1.util_fin_journee, t2.util_precision_planning FROM ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2 WHERE t1.util_id=".$USER_SUBSTITUE." AND t2.util_id=".$idUser);
  $debutJournee = $DB_CX->DbResult(0,0);
  $finJournee   = $DB_CX->DbResult(0,1);
  $precisionAff = 2*$DB_CX->DbResult(0,2);

  //Si l'utilisateur a choisi une couleur de note on l'ajoute dans la clause WHERE de la recherche
  $whereCouleur = "";
  if ($FILTRE_COULEUR != "ALL" && !empty($FILTRE_COULEUR)) {
    $whereCouleur = ($FILTRE_COULEUR == $AgendaFondNotePerso) ? " AND (age_couleur='".$FILTRE_COULEUR."' OR age_couleur='')" : " AND age_couleur='".$FILTRE_COULEUR."'";
  }

  //Preparation au decalage horaire
  prepareDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$moisEnCours,$jourEnCours,$anneeEnCours));
  $datePJSemM1=date("Y-m-d",mktime(12,0,0,$moisEnCours,$premierJourSemaine-1,$anneeEnCours));
  $datePJSemP7=date("Y-m-d",mktime(12,0,0,$moisEnCours,$premierJourSemaine+7,$anneeEnCours));

  //Heure de debut et de fin en fonction des notes hors profil
  $DB_CX->DbQuery("SELECT MIN($age_heure_debut), MAX($age_heure_fin), MAX(IF($age_dateAvant>'$datePJSemM1' AND $age_dateAvant<'$datePJSemP7' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0,1,0)), MAX(IF($age_date>'$datePJSemM1' AND $age_date<'$datePJSemP7' AND $age_heure_debut>=$age_heure_fin,1,0)) FROM ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND (($age_date>'$datePJSemM1' AND $age_date<'$datePJSemP7') OR ($age_dateAvant>'$datePJSemM1' AND $age_dateAvant<'$datePJSemP7' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0)) AND age_aty_id=2".$whereCouleur);
  if ($DB_CX->DbResult(0,0)!=NULL) {
    $debutJournee = min($debutJournee,$DB_CX->DbResult(0,0));
    $finJournee   = max($finJournee,$DB_CX->DbResult(0,1));
    if ($DB_CX->DbResult(0,2)) $debutJournee = 0;
    if ($DB_CX->DbResult(0,3)) $finJournee = 24;
  }

  // Regularisation pour travailler en heure pleine
  $debutJournee = floor($debutJournee);
  $finJournee   = floor($finJournee+0.75);
  $dureeJournee = ($finJournee-$debutJournee)*$precisionAff;

  //Initialisation de la matrice d'affichage
  for ($i=0;$i<$dureeJournee;$i++) {
    for ($j=0;$j<7;$j++) {
      $lienAjout = ((($USER_SUBSTITUE==$idUser || $AFFECTE_NOTE) and ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) or ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION)) ? " style=\"cursor:pointer;\" onmouseover=\"javascript: swapColor(this,".$i.",".$j.",true);\" onmouseout=\"javascript: swapColor(this,".$i.",".$j.",false);\" onclick=\"javascript: nvNote('".($debutSemaine+($j*86400))."','".($debutJournee+($i/$precisionAff))."','');\" title=\"".trad("HEBDOMADAIRE_AJOUT_NOTE_H")."\">&nbsp;" : ">&nbsp;";
      $matAff[$i][$j] = "      <TD class=\"bordTLRB\"".$lienAjout."</TD>\n";
    }
  }
?>
<!-- MODULE PLANNING HEBDOMADAIRE -->
<script language="javascript">
<!--
  var oldColorLigne = '';
  var oldColorJour = '';
  var newColor = '<?php echo $AgendaLigneHover; ?>';
  function swapColor(pCell, pLigne, pColonne, pMouseOver) {
    var trLigne = document.getElementById('ligne' + pLigne);
    var cellHeure = document.getElementById('heure' + (pLigne-pLigne%<?php echo $precisionAff; ?>));
    var cellMinute = document.getElementById('minute' + pLigne);
    var cellJour = document.getElementById('colonne' + pColonne);
    if (pMouseOver) {
      oldColorLigne = trLigne.style.backgroundColor;
      oldColorJour = cellJour.style.backgroundColor;
      cellHeure.style.backgroundColor=newColor;
      cellMinute.style.backgroundColor=newColor;
      cellJour.style.backgroundColor=newColor;
      pCell.style.backgroundColor=newColor;
    } else {
      cellHeure.style.backgroundColor=oldColorLigne;
      cellMinute.style.backgroundColor=oldColorLigne;
      cellJour.style.backgroundColor=oldColorJour;
      pCell.style.backgroundColor=oldColorLigne;
    }
  }
//-->
</script>
  <FORM action="<?php echo "?sid=".$sid."&tcMenu=".$tcMenu."&sd=".$sd; ?>" method="post">
  <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
  <TR>
    <TD width="100%" height="28" nowrap class="sousMenu" style="font-size:10px;"><LABEL for="lundi"><INPUT type="checkbox" name="bt1" value="1"<?php if ($bt1==1) echo " checked"; ?> class="case" id="lundi">&nbsp;<?php echo trad("COMMUN_LUNDI");?></LABEL>&nbsp;&nbsp;
      <LABEL for="mardi"><INPUT type="checkbox" name="bt2" value="1"<?php if ($bt2==1) echo " checked"; ?> class="case" id="mardi">&nbsp;<?php echo trad("COMMUN_MARDI");?></LABEL>&nbsp;&nbsp;
      <LABEL for="mercredi"><INPUT type="checkbox" name="bt3" value="1"<?php if ($bt3==1) echo " checked"; ?> class="case" id="mercredi">&nbsp;<?php echo trad("COMMUN_MERCREDI");?></LABEL>&nbsp;&nbsp;
      <LABEL for="jeudi"><INPUT type="checkbox" name="bt4" value="1"<?php if ($bt4==1) echo " checked"; ?> class="case" id="jeudi">&nbsp;<?php echo trad("COMMUN_JEUDI");?></LABEL>&nbsp;&nbsp;
      <LABEL for="vendredi"><INPUT type="checkbox" name="bt5" value="1"<?php if ($bt5==1) echo " checked"; ?> class="case" id="vendredi">&nbsp;<?php echo trad("COMMUN_VENDREDI");?></LABEL>&nbsp;&nbsp;
      <LABEL for="samedi"><INPUT type="checkbox" name="bt6" value="1"<?php if ($bt6==1) echo " checked"; ?> class="case" id="samedi">&nbsp;<?php echo trad("COMMUN_SAMEDI");?></LABEL>&nbsp;&nbsp;
    <LABEL for="dimanche"><INPUT type="checkbox" name="bt7" value="1"<?php if ($bt7==1) echo " checked"; ?> class="case" id="dimanche">&nbsp;<?php echo trad("COMMUN_DIMANCHE");?></LABEL>&nbsp;&nbsp;&nbsp;</TD>
    <TD align="right" nowrap class="sousMenu" style="text-align:right;"><?php genereListeCouleur(); ?>&nbsp;&nbsp;<?php
  if ($nbJSelect)
    echo "&nbsp;<A href=\"javascript: parent.imprime('".$tcMenu."','".$sd."','".urlencode(str_replace("#","!",$FILTRE_COULEUR))."');\"><IMG src=\"image/impression.gif\" width=\"23\" height=\"21\" border=\"0\" align=\"absmiddle\" title=\"".trad("HEBDOMADAIRE_IMPRIMER")."\"></A>&nbsp;&nbsp;";
?>
    </TD>
  </TR>
  </TABLE>
  </FORM>
  <BR>
<?php
  if ($nbJSelect) {
    echo ("
  <TABLE width=\"99%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
  <TR>
    <TD colspan=2>&nbsp;</TD>
    <TD width=\"100%\" colspan=\"".$nbJSelect."\" height=\"18\" nowrap class=\"bordTLRB\" align=\"center\" bgcolor=\"".$AgendaTitreFond."\"><B><A href=\"javascript: affSemaine('".$lienAvant."');\" class=\"AgendaFleche\"".infoPopup(trad("HEBDOMADAIRE_SEMAINE_PRECEDENTE")." : <B>".date("W",$lienAvant)."</B>").">&laquo;</A>&nbsp;&nbsp;".sprintf(trad("HEBDOMADAIRE_SEMAINE_COURANTE"), date("W",$debutSemaine),date("d",$debutSemaine)." ".strtolower($tabMois[date("n",$debutSemaine)])." ".date("Y",$debutSemaine), date("d",$finSemaine)." ".strtolower($tabMois[date("n",$finSemaine)])." ".date("Y",$finSemaine))."&nbsp;&nbsp;<A href=\"javascript: affSemaine('".$lienApres."');\" class=\"AgendaFleche\"".infoPopup(trad("HEBDOMADAIRE_SEMAINE_SUIVANTE")." : <B>".date("W",$lienApres)."</B>").">&raquo;</A></B></TD>
  </TR>
  <TR align=\"center\">
    <TD colspan=2>&nbsp;</TD>\n");

    $foundAnniv   = false;
    $foundGlobale = false;
    $foundEvent   = false;
    $aAnniv = array();
    $aGlobale = array();
    $aEvent = array();
    $celSize = floor(100/$nbJSelect);
    //Variables utilisees pour les chevauchement de notes
    $iMatPrec=0;
    $hFinPrec=$debutJournee;
    for ($j=0;$j<7;$j++) {
      //On affiche que les jours de la semaine type de l'utilisateur
      if (${"bt".($j+1)}==1) {
        $leJour = mktime(12,0,0,$moisEnCours,$premierJourSemaine+$j,$anneeEnCours);
        //Preparation au decalage horaire
        prepareDecalageH($tzGmt,$tzEte,$tzHiver,$leJour);
        //Coloration des jours feries
        if (in_array(date("j-m",$leJour),$tabJourFerie)) {
          $styl = "dayFerie";
          $bkColor = $CalJourFerie;
        } else {
          $styl = "dayWeek";
          $bkColor = $AgendaTitre2Fond;
        }
        echo "    <TD style=\"background:".((date("Ymd",$leJour)==date("Ymd",$localTime)) ? $CalJourSelection : $bkColor)."\" class=\"".((date("Ymd",$leJour)==date("Ymd",$localTime)) ? "dayWeekCrt" : $styl)."\" width=\"".$celSize."%\" id=\"colonne".$j."\"><A href=\"javascript: affJour('".$leJour."');\" class=\"AgendaTitreJours\"><B>".$tabJour3[date("w",$leJour)]." ".date("d/m",$leJour)."</B></A>";
        if ((($USER_SUBSTITUE==$idUser || $AFFECTE_NOTE) and ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) or ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION))
          echo " <A href=\"javascript: nvNote('".$leJour."','','');\"><IMG src=\"image/ajout_note.gif\" width=\"13\" height=\"15\" border=\"0\" vspace=\"1\" align=\"absmiddle\" title=\"".trad("HEBDOMADAIRE_AJOUT_NOTE_J")."\"></A>";
        echo "</TD>\n";
        $DB_CX->DbQuery("SELECT age_id,age_aty_id,age_heure_debut,age_heure_fin,age_libelle,age_ape_id,age_util_id,CONCAT(".$NOM_UTIL_CREATEUR.") AS nomCreateur,age_detail,aco_termine,age_prive,age_couleur,age_rappel,age_rappel_coeff,age_mere_id,age_nb_participant,age_createur_id,age_date,$age_date AS dateNote,age_date_creation,age_date_modif,age_modificateur_id,CONCAT(".$NOM_UTIL_MODIFICATEUR.") AS nomModificateur,age_lieu,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_id,cal_util_id,cal_partage,cal_societe,cal_adresse,cal_cp,cal_ville,cal_pays,cal_domicile,cal_travail,cal_portable,cal_fax,cal_email,cal_emailpro FROM ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2 WHERE age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE." AND ((($age_date='".date("Y-m-d",$leJour)."' OR ($age_dateAvant='".date("Y-m-d",$leJour)."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0 AND age_aty_id=2))".$whereCouleur.") OR (age_date LIKE '%".date("m-d",$leJour)."' AND DATE_FORMAT(age_date,'%Y%m%d')<=".date("Ymd",$leJour)." AND age_aty_id=1)) AND t1.util_id=age_createur_id AND t2.util_id=age_modificateur_id ORDER BY age_date,age_heure_debut");
        while ($enr = $DB_CX->DbNextRow()) {
          //Recuperation des droits de l'utilisateur sur la note
          attributDroits($enr, $droitModifStatut, $droitModifNotePerso, $droitModifNoteAffectee, $droitSuppOcc, $droitSuppNoteCreee, $droitSuppNoteAffectee, $droitApprNote, $USER_SUBSTITUE, $AFFECTE_NOTE);
          //Decalage des notes en fonction du fuseau horaire
          list($enr['age_heure_debut'],$enr['age_heure_fin'],$enr['dateCreation'],$enr['dateModif']) = decaleNote($tzGmt,$tzEte,$tzHiver,date("Y-m-d",$leJour),$enr['dateNote'],$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
          //Stockage des infos relatives aux anniversaires
          if ($enr['age_aty_id']==1) {
            $infoAge = afficheAge($enr['age_date'],$leJour);
            $aAnniv[$j] .= ($USER_SUBSTITUE==$idUser) ? "<A href=\"javascript: affAnniv('".$enr['age_id']."');\"".$infoAge.">".$enr['age_libelle']."</A><BR>" : "<A".$infoAge.">".$enr['age_libelle']."</A><BR>";
            $foundAnniv = true;
          } else {
            $infoContact = "";
            //Propriete Privee ou Publique de la note
            if ($USER_SUBSTITUE!=$idUser && $enr['cal_util_id']!=$idUser && $enr['age_prive']==1) {
              $enr['age_libelle'] = trad("COMMUN_OCCUPE");
              $enr['age_detail'] = "<P class=\"infoDate\">".trad("COMMUN_NOTE_PRIVEE")."</P>"; // Detail et info de creation non visible si note privee
              $enr['age_couleur'] = $PlanningNotePrivee; // Couleur de note non visible si note privee
              $enr['age_lieu'] = ""; // Emplacement non visible si note privee
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
            //Correction id pour les notes a cheval
            $doubleNote = "";
            if ($enr['age_heure_fin']==24) $doubleNote = "a";
            if ($enr['age_heure_debut']==0) $doubleNote = "b";
            // Droit en modification du statut de la note
            if ($droitModifStatut && !$notePrive) {
              $lienStatut = "<A href=\"/\" onclick=\"javascript: parent.termineNote('".$enr['age_id'].$doubleNote."',".(($NOTE_BARREE) ? "true" : "false")."); return false;\"><IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" id=\"t".$enr['age_id'].$doubleNote."\" title=\"".trad("COMMUN_CHANGER_STATUT")."\"></A>";
            } else {
              $lienStatut = "<IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" alt=\"\">";
            }
            //Couleur de fond de la note si non definie dans la bdd
            if (empty($enr['age_couleur']))
              $enr['age_couleur'] = ($enr['age_util_id']==$USER_SUBSTITUE) ? $AgendaFondNotePerso : $AgendaFondNote;
            // Droit en modification sur la note
            $lien = ($droitModifNotePerso || ($droitModifNoteAffectee && !$notePrive)) ? " href=\"javascript: affNote('".$enr['age_id']."')\"" : "";
            // Info a afficher dans le popup
            $libelleNote = " <A style='font-weight:normal;color:".$AgendaTexteTitrePopup."'>".htmlspecialchars($enr['age_libelle']).((!empty($enr['age_lieu'])) ? "<BR><I>(".$enr['age_lieu'].")</I>" : "")."</A>";
            $detailNote  = htmlspecialchars(nlTObr($enr['age_detail']));
            // Options possibles sur la note (rappel, suppression, appropriation)
            $sOption = "";
            if ($notePrive==false) {
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
                  $sOption .= "&nbsp;<IMG src=\"image/recurrent.gif\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_NOTE_RECURRENTE")."\">";
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
              $sOption .=  "&nbsp;<A href=\"javascript: apprNote('".$enr['age_id']."');\"><IMG src=\"image/appropriation.gif\" border=\"0\" align=\"absmiddle\" title=\"".trad("COMMUN_APPROPRIATION")."\"></A>";
            }
            //Stockage des infos relatives aux notes
            if ($enr['age_aty_id']==2) {
              //Modification des horaires de la note en fonction de la precision d'affichage de l'utilisateur
              $hDeb = ($precisionAff==2 && (($enr['age_heure_debut']-floor($enr['age_heure_debut'])==0.25) || ($enr['age_heure_debut']-floor($enr['age_heure_debut'])==0.75))) ? $enr['age_heure_debut']-0.25 : $enr['age_heure_debut'];
              $hFin = ($precisionAff==2 && (($enr['age_heure_fin']-floor($enr['age_heure_fin'])==0.25) || ($enr['age_heure_fin']-floor($enr['age_heure_fin'])==0.75))) ? $enr['age_heure_fin']+0.25 : $enr['age_heure_fin'];
              $hFin = min($hFin,$finJournee);
              $duree = ($hFin-$hDeb)*$precisionAff;
              $iMat = floor(($enr['age_heure_debut']-$debutJournee)*$precisionAff);
              //Plage horaire de la note
              $debutNote = afficheHeure(floor($enr['age_heure_debut']),$enr['age_heure_debut'],$formatHeure);
              $finNote   = afficheHeure(floor($enr['age_heure_fin']),$enr['age_heure_fin'],$formatHeure);
              //Verification des chevauchement de notes
              if ($hDeb<$hFinPrec) {
                $matAff[$iMatPrec][$j] .= "</DIV>\n        ";
                $iMat = $iMatPrec;
                if ($hFin > $hFinPrec) {
                  $duree = $dureePrec + ($hFin-$hFinPrec)*$precisionAff;
                  $matAff[$iMat][$j] = str_replace("<TD class=\"bordTLRB\" rowspan=\"".$dureePrec."\" bgcolor=\"".$couleurPrec."\">","<TD class=\"bordTLRB\" rowspan=\"".$duree."\" bgcolor=\"".$enr['age_couleur']."\">",$matAff[$iMat][$j]);
                  $dureePrec = $duree;
                  $couleurPrec = $enr['age_couleur'];
                } elseif ($couleurPrec != $enr['age_couleur']) {
                  $matAff[$iMat][$j] = str_replace("<TD class=\"bordTLRB\" rowspan=\"".$dureePrec."\" bgcolor=\"".$couleurPrec."\">","<TD class=\"bordTLRB\" rowspan=\"".$dureePrec."\" bgcolor=\"".$enr['age_couleur']."\">",$matAff[$iMat][$j]);
                  $couleurPrec = $enr['age_couleur'];
                }
              } else {
                if ($hFinPrec!=$debutJournee)
                  $matAff[$iMatPrec][$j] .= "</DIV></TD>\n";
                $iMatPrec = $iMat;
                $matAff[$iMat][$j] = "      <TD class=\"bordTLRB\" rowspan=\"".$duree."\" bgcolor=\"".$enr['age_couleur']."\">";
                $dureePrec = $duree;
                $couleurPrec = $enr['age_couleur'];
              }
              $matAff[$iMat][$j] .= "<DIV style=\"padding:1px;background-color:".$enr['age_couleur']."\">";
              for ($i=1;$i<$duree;$i++) {
                $matAff[$iMat+$i][$j] = "";
              }
              $hFinPrec = max($hFinPrec,$hFin);
              //Stockage des informations sur la note
              $matAff[$iMat][$j] .= $lienStatut;
              $matAff[$iMat][$j] .= "&nbsp;<A".$lien." id=\"n".$enr['age_id'].$doubleNote."\" style=\"text-decoration: ".$styleNote.";\" onmouseover=\"javascript: dtc('".addslashes($debutNote."&rsaquo;".$finNote)."','".addslashes($libelleNote)."','".addslashes($detailNote)."'); return false;\" onmouseout=\"javascript: nd(); return true;\">";
              $matAff[$iMat][$j] .= $debutNote."&rsaquo;";
              $matAff[$iMat][$j] .= $enr['age_libelle']."</A>";
              $matAff[$iMat][$j] .= $sOption;
            }
            //Stockage des infos relatives aux notes couvrant la totalite d'une journee
            elseif ($enr['age_aty_id']==3) {
              $aGlobale[$j] .= "<DIV style=\"padding:1px;background-color:".$enr['age_couleur'].";text-align:left;\">";
              $aGlobale[$j] .= $lienStatut;
              $aGlobale[$j] .= "&nbsp;<A".$lien." id=\"n".$enr['age_id']."\" style=\"text-decoration: ".$styleNote.";\" onmouseover=\"javascript: dtc('".trad("COMMUN_JOURNEE_ENTIERE")."','".addslashes($libelleNote)."','".addslashes($detailNote)."'); return false;\" onmouseout=\"javascript: nd(); return true;\">".$enr['age_libelle']."</A>";
              $aGlobale[$j] .= $sOption."</DIV>";
              $foundGlobale = true;
            }
          }
        }
        // Anniversaire(s) du calepin (y compris les contacts partages)
        $DB_CX->DbQuery("SELECT cal_id,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_util_id,cal_partage,cal_date_naissance FROM ${PREFIX_TABLE}calepin WHERE (cal_util_id=".$USER_SUBSTITUE." OR cal_partage='O') AND cal_date_naissance LIKE '%".date("m-d",$leJour)."' AND DATE_FORMAT(cal_date_naissance,'%Y%m%d')<=".date("Ymd",$leJour));
        while ($enr = $DB_CX->DbNextRow()) {
          $infoAge = afficheAge($enr['cal_date_naissance'],$leJour);
          $aAnniv[$j] .= ($enr['cal_util_id']==$idUser || ($enr['cal_partage']=='O' && $MODIF_PARTAGE)) ? "<A href=\"?ztAction=M&id=".$enr['cal_id']."&sid=".$sid."&tcMenu="._MENU_CONTACT."&tcPlg=".$tcMenu."&sd=".$sd.";\"".$infoAge.">".$enr['nomContact']."</A><BR>" : "<A".$infoAge.">".$enr['nomContact']."</A><BR>";
          $foundAnniv = true;
        }
        // Evenement(s) du jour
        $DB_CX->DbQuery("SELECT DISTINCT eve_id, eve_libelle, eve_util_id, eve_type, DATE_FORMAT(eve_date_debut,'%d/%m/%Y') AS dateDebut, DATE_FORMAT(eve_date_fin,'%d/%m/%Y') AS dateFin FROM ${PREFIX_TABLE}evenement WHERE DATE_FORMAT(eve_date_debut,'%Y%m%d')<='".date("Ymd",$leJour)."' AND DATE_FORMAT(eve_date_fin,'%Y%m%d')>='".date("Ymd",$leJour)."'".(($USER_SUBSTITUE==$idUser) ? " AND (eve_util_id=".$idUser." OR eve_partage='O')" : " AND eve_partage='O'"));
        while ($enr = $DB_CX->DbNextRow()) {
          $popupEvent = ($enr['dateDebut']!=$enr['dateFin']) ? infoPopup(sprintf(trad("COMMUN_DUREE_EVENEMENT"), $enr['dateDebut'], $enr['dateFin'])) : "";
          $aEvent[$j] .= (($MODIF_PARTAGE || $enr['eve_util_id']==$idUser) && ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) ? "<A href=\"javascript: affEvent('".$enr['eve_id']."');\"".$popupEvent.">" : "<A".$popupEvent.">";
          $aEvent[$j] .= "<IMG src=\"image/evenement/evenement".$enr['eve_type'].".gif\" width=\"15\" height=\"15\" alt=\"\" border=\"0\" align=\"absmiddle\" vspace=\"1\" hspace=\"1\">&nbsp;".htmlspecialchars($enr['eve_libelle']);
          $aEvent[$j] .= "</A><BR>";
          //$aEvent[$j] .= ($MODIF_PARTAGE || $enr['eve_util_id']==$idUser) ? "</A><BR>" : "<BR>";
          $foundEvent = true;
        }
      }
      //Si au moins une note a ete enregistree, on ferme la cellule
      if ($hFinPrec!=$debutJournee) {
        $matAff[$iMatPrec][$j] .= "</DIV></TD>\n";
        $iMatPrec=0;
        $hFinPrec=$debutJournee;
      }
    }
    if ($foundEvent) {
      echo "</TR>\n  <TR valign=\"top\" align=\"left\">\n    <TD colspan=\"2\">&nbsp;</TD>\n";
      for ($j=0;$j<7;$j++) {
        if (${"bt".($j+1)}==1) {

          echo "    <TD class=\"bordTLRB\" bgcolor=\"".((!empty($aEvent[$j])) ? $CalJourEvenement : $CalFond)."\">".substr($aEvent[$j],0,strlen($aEvent[$j])-4)."</TD>\n";
        }
      }
    }
    if ($foundAnniv || $foundGlobale) {
      echo "</TR>\n  <TR valign=\"top\" align=\"center\">\n    <TD colspan=\"2\">&nbsp;</TD>\n";
      for ($j=0;$j<7;$j++) {
        if (${"bt".($j+1)}==1) {
          echo "    <TD class=\"bordTLRB\" bgcolor=\"".$CalFond."\">";
          if (!empty($aAnniv[$j]))
            echo "<IMG src=\"image/anniversaire.gif\" width=\"16\" height=\"15\" title=\"".trad("COMMUN_ANNIVERSAIRE")."\" border=\"0\" align=\"absmiddle\" vspace=\"1\" hspace=\"1\">&nbsp;".substr($aAnniv[$j],0,strlen($aAnniv[$j])-4);
          if (!empty($aGlobale[$j]))
            echo $aGlobale[$j];
          echo "</TD>\n";
        }
      }
    }
    echo "    </TR>\n";

    //Affichage du tableau
    $index = 1;
    $frmHeure = ($formatHeure=="H:i") ? "G" : "g";
    for ($i=0;$i<$dureeJournee;$i++) {
      if (!($i%$precisionAff)) {
        $index=1-$index;
        echo "    <TR style=\"background:".$bgColor[$index]."\" valign=\"top\" id=\"ligne".$i."\">\n";
        echo "      <TD width=\"20\" class=\"heure\" nowrap rowspan=\"".$precisionAff."\" align=\"right\" id=\"heure".$i."\">".date($frmHeure,mktime(($debutJournee+($i/$precisionAff)),0,0,1,1,2000))."h</TD>\n";
      } else {
        echo "    <TR style=\"background:".$bgColor[$index]."\" valign=\"top\" id=\"ligne".$i."\">\n";
      }
      echo "      <TD width=\"20\" class=\"minute\" height=\"18\" id=\"minute".$i."\" nowrap>".date("i",mktime(12,(($debutJournee+($i/$precisionAff))*60)%60,0,1,1,2000))."</TD>\n";
      for ($j=0;$j<7;$j++) {
        if (${"bt".($j+1)}==1) {
          echo $matAff[$i][$j];
        }
      }
      echo "    </TR>\n";
    }

    echo "    </TABLE>\n";
    echo "    <DIV class=\"timezone\">".sprintf(trad("COMMUN_FUSEAU_ACTUEL"), (($tzGmt<0) ? "-" : "+").afficheHeure(floor(abs($tzGmt)),abs($tzGmt)), $tzLibelle)."</DIV>\n";
  }
?>
<!-- FIN MODULE PLANNING HEBDOMADAIRE -->
