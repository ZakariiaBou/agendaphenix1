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

  if ($rdPrec == "EXCLU")
    $rdDans = 1;
  elseif ($rdPrec != "AND")
    $rdPrec = "OR";
  if ($rdAff != 2) {
    $rdAff = 1;
    $zlCaractere = 200;
  } else // Choix de l'affichage pour la requete
    $affDetail = ",age_detail";
  if (!$ckFini)
    $zlTermine = 0;
  if ($rdTri != "ASC")
    $rdTri = "DESC";
  $iColor = 1;
  $nbOptions = $ckDansLib + $ckDansDet + $ckDansLieu;
  if ($nbOptions == 0 && $rdPrec != "EXCLU")
    $ckDansLib=$nbOptions=1;
  $zlContactAssocie += 0;
?>

<!-- MODULE PLANNING RECHERCHE -->
<?php include("inc/checkdate.js.php"); ?>
  <SCRIPT language="JavaScript" type="text/javascript">
  <!--
    //Recherche d'un libelle a partir de la liste de choix
    function addLib(_select) {
      if (_select.selectedIndex>0) {
        document.Form1.ztCherche.value=_select[_select.selectedIndex].text+" ";
        document.Form1.ztCherche.focus();
      }
    }
    function saisieOK(theForm) {
      var _dateValide = ((theForm.ztDateDebut.value == "" || chk_date_format(theForm.ztDateDebut)) && (theForm.ztDateFin.value == "" || chk_date_format(theForm.ztDateFin)));
      var _choixValide = ((theForm.rdPrec[2].checked) || ((!theForm.rdPrec[2].checked) && (theForm.ckDansLib.checked || theForm.ckDansDet.checked || theForm.ckDansLieu.checked)));
      if (_choixValide && _dateValide) {
        theForm.submit();
        return (true);
      } else if (!_choixValide) {
        alert('<?php echo trad("RECHERCHE_SELECT_ZONE");?>');
      } else {
        alert('<?php echo trad("RECHERCHE_VERIF_DATE");?>');
      }
      return (false);
    }
  //-->
  </SCRIPT>
  <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
  <TR>
    <TD height="28" class="sousMenu"><?php echo trad("RECHERCHE_TITRE");?></TD>
  </TR>
  </TABLE>
  <BR>
  <FORM action="agenda.php" method="post" name="Form1">
    <INPUT type="hidden" name="sid" value="<?php echo $sid; ?>">
    <INPUT type="hidden" name="tcMenu" value="<?php echo $tcMenu; ?>">
    <INPUT type="hidden" name="tcPlg" value="<?php echo $tcPlg; ?>">
    <INPUT type="hidden" name="sd" value="<?php echo $sd; ?>">
  <TABLE align="center" cellspacing="0" cellpadding="0" width="500" border="0">
    <TR height="21" bgcolor="<?php echo $bgColor[$iColor%2]; ?>">
      <TD class="tabIntitule"><?php echo trad("RECHERCHE_CHERCHER");?></TD>
      <TD class="tabInput"><?php
  //Liste des libelles personnalises de l'utilisateur connecte et partages
  $DB_CX->DbQuery("SELECT lib_id, lib_nom FROM ${PREFIX_TABLE}libelle WHERE lib_util_id=".$idUser." OR (lib_util_id!=".$idUser." AND lib_partage='O') ORDER BY lib_nom");
  if ($DB_CX->DbNumRows()) {
    echo ("<SELECT name=\"zlLibelle\" onchange=\"javascript: addLib(this);\">
        <OPTION value=\"0\">-- ".trad("RECHERCHE_LIBELLES_PERSO")." --</OPTION>\n");
    while ($DB_CX->DbNextRow())
      echo "        <OPTION value=\"".$DB_CX->Row[0]."\">".htmlspecialchars($DB_CX->Row[1])."</OPTION>\n";
    echo ("      </SELECT><BR>");
  }
?><INPUT type="text" class="Texte" name="ztCherche" size="50" value="<?php echo htmlspecialchars(stripslashes($ztCherche)); ?>">&nbsp;&nbsp;<INPUT type="button" class="PickList" name="btRecherche" value="<?php echo trad("RECHERCHE_OK");?>" title="<?php echo trad("RECHERCHE_LANCER");?>" style="height:16px" onclick="javascript: return saisieOK(document.Form1);"></TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule"><?php echo trad("RECHERCHE_PRECISION");?></TD>
      <TD class="tabInput"><LABEL for="precUn"><INPUT type="radio" name="rdPrec" id="precUn" value="OR" class="Case"<?php if ($rdPrec=="OR") {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_PRECISION_UN");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="precTous"><INPUT type="radio" name="rdPrec" id="precTous" value="AND" class="Case"<?php if ($rdPrec=="AND") {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_PRECISION_TOUS");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="precExclu"><INPUT type="radio" name="rdPrec" id="precExclu" value="EXCLU" class="Case"<?php if ($rdPrec=="EXCLU") {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_PRECISION_EXACT");?></LABEL></TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule"><?php echo trad("RECHERCHE_DANS");?></TD>
      <TD class="tabInput"><LABEL for="dansLib"><INPUT type="checkbox" name="ckDansLib" id="dansLib" value="1" class="Case"<?php if ($ckDansLib==1) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_DANS_LIBELLE");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="dansDet"><INPUT type="checkbox" name="ckDansDet" id="dansDet" value="2" class="Case"<?php if ($ckDansDet==2) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_DANS_DETAIL");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="dansLieu"><INPUT type="checkbox" name="ckDansLieu" id="dansLieu" value="4" class="Case"<?php if ($ckDansLieu==4) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_DANS_EMPLACEMENT");?></LABEL></TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule" nowrap><?php echo trad("RECHERCHE_COULEUR");?></TD>
      <TD class="tabInput"><?php
    //Recuperation des couleurs/categories de notes
    $tabTemp    = array("&lt; ".trad("RECHERCHE_COULEUR_CHOIX")." &gt;" => "");
    $tabCouleur = array_merge($tabTemp,getListeCouleur());

    //Construction de la liste des couleurs/categories de notes
    reset($tabCouleur);
    echo "<SELECT name=\"zlCouleur\" style=\"background-color:".((!empty($zlCouleur)) ? $zlCouleur : $FormulaireFondInput).";\" onchange=\"javascript: changeCouleurListe(this,null);\">\n";
    while (list($key, $val) = each($tabCouleur)) {
      $selected = ($val==$zlCouleur) ? " selected" : "";
      echo "        <OPTION value=\"".$val."\" style=\"background-color:".(($val!="") ? $val : $FormulaireFondInput).";\"".$selected.">".$key."</OPTION>\n";
    }
?>
      </SELECT></TD>
    </TR>
<?php
    // Recuperation des contacts de l'utilisateur et ceux qui sont partages
    $DB_CX->DbQuery("SELECT DISTINCT cal_id, LTRIM(CONCAT(cal_nom,' ',cal_prenom)) AS nomContact FROM ${PREFIX_TABLE}calepin, ${PREFIX_TABLE}agenda, ${PREFIX_TABLE}agenda_concerne WHERE aco_util_id=".$USER_SUBSTITUE." AND age_id=aco_age_id AND cal_id=age_cal_id ORDER BY nomContact");
    // Le choix du contact n'est pas affiche si le calepin est vide
    if ($DB_CX->DbNumRows()) {
?>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule" nowrap><?php echo trad("RECHERCHE_CONTACT");?></TD>
      <TD class="tabInput"><SELECT name="zlContactAssocie">
      <OPTION value="0"></OPTION>
<?php
      $lettreCrt = "";
      while ($cal = $DB_CX->DbNextRow()) {
        // Premiere lettre
        if ($lettreCrt!=substr($cal['nomContact'],0,1)) {
          if ($lettreCrt!="") {
            echo "      </OPTGROUP>\n";
          }
          $lettreCrt = substr($cal['nomContact'],0,1);
          echo "      <OPTGROUP label=\"".htmlspecialchars($lettreCrt)."\">\n";
        }
        $selected = ($cal['cal_id']==$zlContactAssocie) ? " selected" : "";
        echo "        <OPTION value=\"".$cal['cal_id']."\"".$selected.">".htmlspecialchars($cal['nomContact'])."</OPTION>\n";
      }
      echo "      </OPTGROUP>\n";
?>
      </SELECT></TD>
    </TR>
<?php
    }
?>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule" nowrap><?php echo trad("RECHERCHE_APRES");?></TD>
      <TD class="tabInput"><INPUT type="text" class="Texte" name="ztDateDebut" id="ztDateDebut" size=12 maxlength=10 value="<?php echo $ztDateDebut; ?>" title="<?php echo trad("RECHERCHE_FORMAT_DATE");?>" onKeyPress="return onlyChar(event);">&nbsp;<INPUT type="button" id="btCal1" value="..." class="picklist" style="height:16px" title="<?php echo trad("RECHERCHE_AFFICHE_CALENDRIER");?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<B><?php echo trad("RECHERCHE_AVANT");?></B> <INPUT type="text" class="Texte" name="ztDateFin" id="ztDateFin" size=12 maxlength=10 value="<?php echo $ztDateFin; ?>" title="<?php echo trad("RECHERCHE_FORMAT_DATE");?>" onKeyPress="return onlyChar(event);">&nbsp;<INPUT type="button" id="btCal2" value="..." class="picklist" style="height:16px" title="<?php echo trad("RECHERCHE_AFFICHE_CALENDRIER");?>">&nbsp;&nbsp;&nbsp;<I>(<?php echo trad("RECHERCHE_FORMAT_DATE");?>)</I></TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule"><?php echo trad("RECHERCHE_EXCLURE");?></TD>
      <TD class="tabInput"><LABEL for="saufFini"><INPUT type="checkbox" name="ckFini" id="saufFini" value="1" class="Case"<?php if ($ckFini==1) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_NOTES");?> </LABEL><SELECT name="zlTermine" size="1" onFocus="document.Form1.ckFini.checked='true';"><OPTION value="0"<?php if ($zlTermine!=1) {echo " selected";} ?>><?php echo trad("RECHERCHE_NOTES_TERMINES");?></OPTION><OPTION value="1"<?php if ($zlTermine==1) {echo " selected";} ?>><?php echo trad("RECHERCHE_NOTES_ACTIVES");?></OPTION></SELECT>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="saufRecur"><INPUT type="checkbox" name="ckRecur" id="saufRecur" value="1" class="Case"<?php if ($ckRecur==1) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_NOTES_RECURENTES");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="saufAffecte"><INPUT type="checkbox" name="ckAffecte" id="saufAffecte" value="1" class="Case"<?php if ($ckAffecte==1) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_NOTES_AFFECTEES");?></LABEL></TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule"><?php echo trad("RECHERCHE_AFFICHAGE");?></TD>
      <TD nowrap class="tabInput"><LABEL for="affLib"><INPUT type="radio" name="rdAff" id="affLib" value="1" class="Case"<?php if ($rdAff==1) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_AFFICHAGE_LIBELLE");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="affLibDet"><INPUT type="radio" name="rdAff" id="affLibDet" value="2" class="Case"<?php if ($rdAff==2) {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_AFFICHAGE_DETAIL");?></LABEL>
        (<?php echo trad("RECHERCHE_AFFICHAGE_LIMITE");?> <SELECT name="zlCaractere" onFocus="document.Form1.rdAff[1].checked='true';">
<?php
  for ($i=100;$i<501;$i+=100) {
    $selected = ($zlCaractere==$i) ? " selected" : "";
    echo "        <OPTION value=\"".$i."\"".$selected.">".$i."</OPTION>\n";
  }
?>
      </SELECT> <?php echo trad("RECHERCHE_AFFICHAGE_CARACTERES");?>)</TD>
    </TR>
    <TR height="21" bgcolor="<?php echo $bgColor[++$iColor%2]; ?>">
      <TD class="tabIntitule" nowrap><?php echo trad("RECHERCHE_TRIER");?></TD>
      <TD nowrap class="tabInput"><SELECT name="zlTriPar">
        <OPTION value="date"<?php if ($zlTriPar=="date") {echo " selected";} ?>><?php echo trad("RECHERCHE_TRIER_DATE");?></OPTION>
        <OPTION value="libelle"<?php if ($zlTriPar=="libelle") {echo " selected";} ?>><?php echo trad("RECHERCHE_TRIER_LIBELLE");?></OPTION>
      </SELECT>&nbsp;&nbsp;<LABEL for="triAsc"><INPUT type="radio" name="rdTri" id="triAsc" value="ASC" class="Case"<?php if ($rdTri=="ASC") {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_TRIER_CROISSANT");?></LABEL>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<LABEL for="triDesc"><INPUT type="radio" name="rdTri" id="triDesc" value="DESC" class="Case"<?php if ($rdTri=="DESC") {echo " checked";} ?>>&nbsp;<?php echo trad("RECHERCHE_TRIER_DECROISSANT");?></LABEL></TD>
    </TR>
  <SCRIPT type="text/javascript">
  <!--
    Calendar.setup( {
      inputField : "ztDateDebut",    // ID of the input field
      ifFormat   : "%d/%m/%Y",  // the date format
      button     : "btCal1"      // ID of the button
    } );
    Calendar.setup( {
      inputField : "ztDateFin",    // ID of the input field
      ifFormat   : "%d/%m/%Y",  // the date format
      button     : "btCal2"      // ID of the button
    } );
<?php
    if (!$ztCherche)
      echo("    document.Form1.ztCherche.focus();\n");
?>
  //-->
  </SCRIPT>
  </TABLE>
  </FORM>
  <BR>
<?php
  echo "  <TABLE cellspacing=\"0\" cellpadding=\"0\" width=\"500\" border=\"0\" style=\"border:solid 1px ".$AgendaBordureTableau.";\">\n";
  // Traitement de la recherche
  if (!empty($ztCherche) || !empty($zlCouleur) || $zlContactAssocie) {
    $sql = "";
    if (!empty($ztCherche)) {
      // Transformation des saisies pour le sql
      $sqlLib = $sqlDet = "";
      if ($rdPrec!="EXCLU") {
        $ztCherche = explode(" ",$ztCherche);
        for ($i=0;$i<count($ztCherche);$i++) {
          if (trim($ztCherche[$i]) != "") {
            $precision = ($i>0) ? " ".$rdPrec." " : "";
            if ($ckDansLib==1) // Recherche dans le libelle de la note
              $sqlLib .= $precision."LOWER(age_libelle) LIKE LOWER('%".$ztCherche[$i]."%')";
            if ($ckDansDet==2) // Recherche dans le detail de la note
              $sqlDet .= $precision."LOWER(age_detail) LIKE LOWER('%".$ztCherche[$i]."%')";
            if ($ckDansLieu==4) // Recherche dans l'emplacement de la note
              $sqlLieu .= $precision."LOWER(age_lieu) LIKE LOWER('%".$ztCherche[$i]."%')";
          }
        }
        switch ($nbOptions) {
          case 1 :
            $sql .= " AND (".$sqlLib.")";
            break;
          case 2 :
            $sql .= " AND (".$sqlDet.")";
            break;
          case 3 :
            $sql .= " AND ((".$sqlLib.") OR (".$sqlDet."))";
            break;
          case 4 :
            $sql .= " AND (".$sqlLieu.")";
            break;
          case 5 :
            $sql .= " AND ((".$sqlLib.") OR (".$sqlLieu."))";
            break;
          case 6 :
            $sql .= " AND ((".$sqlDet.") OR (".$sqlLieu."))";
            break;
          case 7 :
            $sql .= " AND ((".$sqlLib.") OR (".$sqlDet.") OR (".$sqlLieu."))";
            break;
        }
      } else {
        $sql .= " AND LOWER(age_libelle)=LOWER('".$ztCherche."')";
      }
    }
    // Critere sur la couleur
    if (!empty($zlCouleur)) {
      $sql .= " AND age_couleur='".$zlCouleur."'";
    }
    // Critere sur le contact associe
    if ($zlContactAssocie) {
      $sql .= " AND age_cal_id=".$zlContactAssocie;
    }
    // Critere sur la date de debut
    if (!empty($ztDateDebut)) {
      $tabDate = explode("/",$ztDateDebut);
      $ztDateDebut = $tabDate[2]."-".$tabDate[1]."-".$tabDate[0];
      $sql .= " AND age_date>='".$ztDateDebut."'";
    }
    // Critere sur la date de fin
    if (!empty($ztDateFin)) {
      $tabDate = explode("/",$ztDateFin);
      $ztDateFin = $tabDate[2]."-".$tabDate[1]."-".$tabDate[0];
      $sql .= " AND age_date<='".$ztDateFin."'";
    }

    // Exclusion
    $exclusion = "";
    if ($ckFini == 1)
      $exclusion .= " AND aco_termine=".($zlTermine+0);
    if ($ckRecur == 1)
      $exclusion .= " AND age_mere_id=0";
    if ($ckAffecte == 1)
      $exclusion .= " AND age_util_id=".$USER_SUBSTITUE;
    if ($USER_SUBSTITUE!=$idUser) // En cas de substitution, on exclu automatiquement les notes privees
      $exclusion .= " AND age_prive=0";
    // Ordre de tri
    $orderBy = " ORDER BY ".(($zlTriPar != "libelle") ? "age_date ".$rdTri.", age_heure_debut" : "age_libelle ".$rdTri.", age_date, age_heure_debut");
    $DB_CX->DbQuery("SELECT DATE_FORMAT(age_date,'%e/%c/%Y') AS ageDate,age_heure_debut,age_heure_fin,age_util_id,CONCAT(".$FORMAT_NOM_UTIL.") AS nomCreateur,aco_termine,age_libelle,age_id,age_nb_participant,age_createur_id,age_aty_id,age_date_creation,age_date_modif,age_lieu,age_cal_id,CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact,cal_util_id,cal_partage".$affDetail." FROM ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}utilisateur WHERE age_aty_id!=1 AND age_id=aco_age_id AND aco_util_id=".$USER_SUBSTITUE.$sql." AND util_id=age_createur_id".$exclusion.$orderBy);
    $nb = $DB_CX->DbNumRows();
    $pluriel = ($nb > 1) ? trad("COMMUN_PLURIEL") : "";
    if ($nb == 0)
      $resRecherche = "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD colspan=\"".(($rdAff==1) ? "3" : "2")."\" align=\"center\" class=\"bordTLRB\"><P class=\"rouge\">".trad("RECHERCHE_AUCUNE_NOTE")."</P></TD>\n  </TR>\n";
    else {
      // Affichage des resultats
      $index = 0;
      $resRecherche = "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD colspan=\"".(($rdAff==1) ? "3" : "2")."\" align=\"center\" class=\"bordTLRB\"><P class=\"vert\">".sprintf(trad("RECHERCHE_NOTE_CORRESPOND"), $nb, $pluriel)."</P></TD>\n  </TR>\n";
      while ($enr = $DB_CX->DbNextRow()) {
        attributDroits($enr, $droitModifStatut, $droitModifNotePerso, $droitModifNoteAffectee, $droitSuppOcc, $droitSuppNoteCreee, $droitSuppNoteAffectee, $droitApprNote, $USER_SUBSTITUE, $AFFECTE_NOTE);
        $index = 1 - $index;
        // Transformation de la date de debut de la note en timestamp PHP
        list($j,$m,$a) = explode("/",$enr['ageDate']);
        $tsNote = mktime(12,0,0,$m,$j,$a);
        //Decalage des notes en fonction du fuseau horaire
        list($enr['age_heure_debut'],$enr['age_heure_fin'],$enr['dateCreation'],$enr['dateModif']) = decaleNote($tzGmt,$tzEte,$tzHiver,0,date("Y-m-d",$tsNote),$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
        $plageNote = ($enr['age_aty_id']==2) ? afficheHeure(floor($enr['age_heure_debut']),$enr['age_heure_debut'],$formatHeure)."&rsaquo;".afficheHeure(floor($enr['age_heure_fin']),$enr['age_heure_fin'],$formatHeure) : trad("COMMUN_JOURNEE_ENTIERE");
        $imgTemoin = ($enr['aco_termine'] == 1) ? "puce_ok.gif" : "puce_ko.gif";
        $createurNote = ($enr['age_createur_id']!=$USER_SUBSTITUE) ? " (".sprintf(trad("RECHERCHE_CREATEUR_NOTE"), $enr['nomCreateur']).")" : "";
        $lienNote = ($droitModifNotePerso || $droitModifNoteAffectee) ? "<A href=\"javascript: affNote('".$enr['age_id']."')\"><B>".$enr['age_libelle']."</B></A>" : "<B>".$enr['age_libelle']."</B>";
        $lienNote .= ($droitApprNote) ? "&nbsp;<A href=\"javascript: apprNote('".$enr['age_id']."');\"><IMG src=\"image/appropriation.gif\" alt=\"".trad("COMMUN_APPROPRIATION")."\" border=\"0\" align=\"absmiddle\"></A>" : "";
        if (!empty($enr['nomContact'])) {
          $lienContact = ($enr['cal_util_id']==$idUser || ($enr['cal_partage']=='O' && $MODIF_PARTAGE)) ? "<A href=\"javascript: affContact('".$enr['age_cal_id']."');\">".htmlspecialchars($enr['nomContact'])."</A>" : htmlspecialchars($enr['nomContact']);
          $contactAssocie = "<BR>".trad("RECHERCHE_CONTACT_ASSOCIE")." : <B>".$lienContact."</B>";
        } else {
          $contactAssocie = "";
        }
        if ($rdAff == 1) {
          $resRecherche .= "  <TR bgcolor=\"".$bgColor[$index]."\" style=\"padding:2px;\">\n    <TD colspan=\"3\" class=\"bordT\">&nbsp;<IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" title=\"".trad("RECHERCHE_STATUT")."\">&nbsp;".$lienNote.((!empty($enr['age_lieu'])) ? "<BR><I>(".$enr['age_lieu'].")</I>" : "").$contactAssocie."</TD>\n  </TR>\n";
          $resRecherche .= "  <TR bgcolor=\"".$bgColor[$index]."\" style=\"padding:2px;\">\n    <TD width=\"100%\" align=\"center\">".$createurNote."</TD>    <TD nowrap>&nbsp;<A href=\"agenda.php?sid=".$sid."&sd=".$tsNote."\"><B>".$tabJour[date("w",$tsNote)]." ".date("d/m/y",$tsNote)."</B></A>&nbsp;</TD>\n    <TD nowrap>".$plageNote."&nbsp;";
        } else {
          $enr['age_detail'] = str_replace(chr(13).chr(10)," ",$enr['age_detail']);
          if (strlen($enr['age_detail']) > $zlCaractere)
            $enr['age_detail'] = substr($enr['age_detail'],0,$zlCaractere)." ...";
          $resRecherche .= "  <TR bgcolor=\"".$bgColor[$index]."\">\n    <TD rowspan=\"2\" width=\"65\" height=\"60\" align=\"center\" nowrap class=\"bordTLRB\"><A href=\"agenda.php?sid=".$sid."&sd=".$tsNote."\"><B>".$tabJour[date("w",$tsNote)]."<BR>".date("d/m/y",$tsNote)."</B></A></TD>\n    <TD height=\"15\" bgcolor=\"".$AgendaFondNotePerso."\" class=\"bordTLRB\" style=\"padding-left:4px;\">".$plageNote.$createurNote."</TD>\n  </TR>\n";
          $resRecherche .= "  <TR>\n    <TD width=\"100%\" bgcolor=\"".$bgColor[$index]."\" class=\"bordTLRB\" style=\"padding-left:4px;\" valign=\"top\" height=\"45\"><IMG src=\"image/".$imgTemoin."\" width=\"6\" height=\"6\" border=\"0\" title=\"".trad("RECHERCHE_STATUT")."\">&nbsp;".$lienNote.((!empty($enr['age_lieu'])) ? "<BR><I>(".$enr['age_lieu'].")</I>" : "").$contactAssocie."<BR>".$enr['age_detail'];
        }
        $resRecherche .= "</TD>\n  </TR>\n";
      }
    }
    echo $resRecherche;
  }
  else
    echo "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD colspan=\"".(($rdAff==1) ? "3" : "2")."\" align=\"center\">".trad("RECHERCHE_SAISIR_CRITERE")."</TD>\n  </TR>\n";
?>
  </TABLE>
<?php if ($nb != 0) echo "  <DIV class=\"timezone\" style=\"text-align:center;\">".sprintf(trad("COMMUN_FUSEAU_ACTUEL"), (($tzGmt<0) ? "-" : "+").afficheHeure(floor(abs($tzGmt)),abs($tzGmt)), $tzLibelle)."</DIV>\n"; ?>
<!-- FIN MODULE PLANNING RECHERCHE -->
