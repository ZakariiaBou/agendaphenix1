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

  $id += 0;
  $ztAction = "INSERT";
  $titrePage = trad("MEMO_TITRE_ENREG");
  $createur = $idUser;
  if ($id) {
    // Edition d'un memo
    $DB_CX->DbQuery("SELECT mem_titre, mem_contenu, mem_partage, mem_util_id FROM ${PREFIX_TABLE}memo WHERE mem_id=".$id." AND (mem_util_id=".$idUser." OR mem_partage='O')");
    if ($enr = $DB_CX->DbNextRow()) {
      $titre = $enr['mem_titre'];
      $contenu = $enr['mem_contenu'];
      $ckPartage = $enr['mem_partage'];
      $createur = $enr['mem_util_id'];
      $ztAction = "UPDATE";
      $titrePage = trad("MEMO_TITRE_MODIF");
      if ($createur!=$idUser) {
        $titrePage .= " ".trad("MEMO_TITRE_PARTAGE");
      }
    } else  {
      $id = 0;
    }
  }
?>
<!-- MODULE MEMO -->
  <SCRIPT language="JavaScript" type="text/javascript">
  <!--
    //Saisie d'un memo
    function saisieOK(theForm) {
      if (trim(theForm.ztTitre.value) == "") {
        window.alert("<?php echo trad("MEMO_ALERTE_TITRE");?>");
        theForm.ztTitre.focus();
        return (false);
      }

      PrepareSave();
      theForm.submit();
      return (true);
    }
    //Active/Desactive le choix de partage si le memo est affecte a un autre utilisateur
    function autorisePartage(_val) {
      if (document.FormMemo.ckPartage != null) {
        if (_val!='<?php echo $idUser; ?>') {
          document.FormMemo.ckPartage.checked = false;
          document.FormMemo.ckPartage.disabled = true;
        } else {
          document.FormMemo.ckPartage.disabled = false;
        }
      }
    }
  //-->
  </SCRIPT>
  <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
  <TR>
    <TD height="28" class="sousMenu"><?php echo $titrePage; ?></TD>
  </TR>
  </TABLE>
  <BR>
  <FORM action="agenda_traitement.php" method="post" name="FormMemo">
    <INPUT type="hidden" name="sid" value="<?php echo $sid; ?>">
    <INPUT type="hidden" name="sd" value="<?php echo gmdate("Y-n-j", $sd); ?>">
    <INPUT type="hidden" name="id" value="<?php echo $id; ?>">
    <INPUT type="hidden" name="ztFrom" value="memo">
    <INPUT type="hidden" name="ztAction" value="<?php echo $ztAction; ?>">
    <INPUT type="hidden" name="tcMenu" value="<?php echo $tcMenu; ?>">
    <INPUT type="hidden" name="tcPlg" value="<?php echo $tcPlg; ?>">
<?php
  if ($createur!=$idUser) {
    echo ("    <INPUT type=\"hidden\" name=\"ckPartage\" value=\"O\">
    <INPUT type=\"hidden\" name=\"zlUtilisateur\" value=\"".$createur."\">
    <TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\">\n");
  } else {
    $DB_CX->DbQuery("SELECT DISTINCT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE util_id=".$idUser." OR (util_autorise_affect ='1') OR (util_autorise_affect IN ('2','3') AND paf_consultant_id=".$idUser.") ORDER BY nomUtil");
    if ($DB_CX->DbNumRows() == 1) {
      echo "    <INPUT type=\"hidden\" name=\"zlUtilisateur\" value=\"".$idUser."\">\n";
    }
    echo "    <TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"0\" width=\"600\">\n";
    if ($DB_CX->DbNumRows()>1) {
      echo ("    <TR bgcolor=\"".$bgColor[1]."\">
      <TD class=\"tabIntitule\" nowrap>".trad("MEMO_PERSONNE_CONCERNEE")."&nbsp;</TD>
      <TD class=\"tabInput\"><SELECT name=\"zlUtilisateur\" id=\"zlUtilisateur\" size=\"1\" onchange=\"javascript: autorisePartage(this.value);\">\n");
      while ($rsUtil = $DB_CX->DbNextRow()) {
        $selected = ($idUser == $rsUtil['util_id']) ? " selected" : "";
        echo "        <OPTION value=\"".$rsUtil['util_id']."\"".$selected.">".$rsUtil['nomUtil']."</OPTION>\n";
      }
      echo ("      </SELECT></TD>
    </TR>\n");
    }
  }
?>
    <TR bgcolor="<?php echo $bgColor[0]; ?>">
      <TD class="tabIntitule"><?php echo trad("MEMO_TITRE");?></TD>
      <TD class="tabInput" nowrap height="21" width="471"><INPUT type="text" class="Texte" name="ztTitre" value="<?php echo htmlspecialchars(stripslashes($titre)); ?>" style="width:469px" maxlength="150"></TD>
    </TR>
    <TR bgcolor="<?php echo $bgColor[1]; ?>">
      <TD class="tabIntitule"><?php echo trad("MEMO_CONTENU");?>&nbsp;</TD>
      <TD class="tabInput" nowrap><?php genereTextArea("ztContenu",$contenu,469,7); ?></TD>
    </TR>
<?php if ($createur==$idUser) { ?>
    <TR bgcolor="<?php echo $bgColor[0]; ?>" height="21">
      <TD class="tabIntitule" nowrap><?php echo trad("MEMO_LIB_PARTAGE");?></TD>
      <TD class="tabInput" nowrap><LABEL for="partageMemo"><INPUT type="checkbox" name="ckPartage" id="partageMemo" value="O" class="Case"<?php if ($ckPartage=='O') {echo " checked";} ?>>&nbsp;<?php echo trad("MEMO_COCHER_PARTAGE");?></LABEL></TD>
    </TR>
<?php } ?>
    </TABLE>
    <BR><INPUT type="button" name="btEnregistre" value="<?php echo trad("MEMO_BT_ENREGISTRER");?>" onClick="javascript: return saisieOK(document.FormMemo);" class="bouton">&nbsp;&nbsp;&nbsp;<INPUT type="button" name="btAnnule" value="<?php echo trad("MEMO_BT_ANNULER");?>" onclick="javascript: btAnnul();" class="bouton"><?php if ($ztAction == "UPDATE") { ?>&nbsp;&nbsp;&nbsp;<INPUT type="button" name="btSupprime" value="<?php echo trad("MEMO_BT_SUPPRIMER");?>" onclick="javascript: if (confirm('<?php echo trad("MEMO_ALERTE_SUP");?>')) { document.FormMemo.ztAction.value='DELETE'; document.FormMemo.submit(); }" class="Bouton"><?php } ?>
  </FORM>
<?php
  //Liste des differents memos
  $DB_CX->DbQuery("SELECT mem_id, mem_titre, mem_contenu, mem_util_id FROM ${PREFIX_TABLE}memo WHERE mem_util_id=".$idUser." OR mem_partage='O' ORDER BY mem_id ASC");
  if ($DB_CX->DbNumRows()) {
    echo ("  <BR>
  <FORM>
    <TABLE width=\"600\" border=\"0\" cellspacing=\"0\" cellpadding=\"2\">\n");
    $index = 0;
    while ($enr = $DB_CX->DbNextRow()) {
      $index = 1 - $index;
      echo ("    <TR bgcolor=\"".$bgColor[$index]."\">
      <TD width=\"420\" class=\"bordTL\"><B>".$enr['mem_titre']."</B></TD>
      <TD width=\"45\" class=\"bordTR\" nowrap>&nbsp;");
      if ($enr['mem_util_id']==$idUser || $MODIF_PARTAGE) { // Modif du memo
        echo "<INPUT type=\"button\" class=\"bouton\" name=\"btModif\" value=\"".trad("MEMO_M")."\" title=\"".trad("MEMO_BT_MODIFIER")."\" style=\"width:16px\" onclick=\"javascript: window.location.href='?id=".$enr['mem_id']."&tcType="._TYPE_MEMO."&sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."';\">&nbsp;";
      }
      if ($enr['mem_util_id']==$idUser) { // Suppression du memo
        echo "<INPUT type=\"button\" class=\"bouton\" name=\"btSuppr\" value=\"".trad("MEMO_S")."\" title=\"".trad("MEMO_BT_SUPPRIMER")."\" style=\"width:16px\" onclick=\"javascript: if (confirm('".trad("MEMO_ALERTE_SUP")."')) window.location.href='agenda_traitement.php?ztFrom=memo&ztAction=DELETE&id=".$enr['mem_id']."&sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".gmdate("Y-n-j", $sd)."';\">&nbsp;";
      }
      echo ("</TD>
    </TR>
    <TR bgcolor=\"".$bgColor[$index]."\">
      <TD colspan=\"2\" class=\"bordLRB\">".nlTObr($enr['mem_contenu'])."</TD>
    </TR>\n");
    }
    echo ("    </TABLE>
  </FORM>\n");
  }

  if (!$id) {
    echo ("  <SCRIPT type=\"text/javascript\">
  <!--
    document.FormMemo.ztTitre.focus();
  //-->
  </SCRIPT>\n");
  }
?>
<!-- FIN MODULE MEMO -->
