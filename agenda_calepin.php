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


// Scripts d'import
include("agenda_calepin_import.php");


// Indique si une lettre contient des entrees ou non
  function alim_alphabet(&$alphabet, $A){
    global $DB_CX, $PREFIX_TABLE, $idUser;
    $DB_CX->DbQuery("SELECT DISTINCT UPPER(SUBSTRING(cal_nom,1,1)) AS initiale FROM ${PREFIX_TABLE}calepin WHERE cal_util_id=".$idUser." OR (cal_util_id!=".$idUser." AND cal_partage='O') ORDER BY initiale");
    while ($enr = $DB_CX->DbNextRow())
      $alphabet[ord($enr['initiale'])-$A] = 1;
  }
//--------------------------------------------------


// Affiche la liste des lettres en haut.
  function aff_alph($lettre = "A") {
    global $sid, $sd, $tcMenu, $tcPlg, $CalepinValide, $CalepinNonValide, $CalepinSelection, $CalepinFondSelection;
    $alphabet = array(0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
    $A = ord("A");
    alim_alphabet($alphabet,$A);
    echo "<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" align=\"center\">\n";
    echo "      <TR>\n";
    for ($i=$A;$i<$A+26;$i++) {
      if ($lettre == chr($i)) {
        $bgCoul   = " width=\"19\" height=\"19\" bgcolor=\"".$CalepinFondSelection."\" class=\"CalFondJour\"";
        $fontCoul = $CalepinSelection;
      }
      elseif ($alphabet[$i-$A] == 0) {
        $bgCoul   = " width=\"17\" height=\"17\"";
        $fontCoul = $CalepinNonValide;
      }
      else {
        $bgCoul   = " width=\"17\" height=\"17\"";
        $fontCoul = $CalepinValide;
      }
      echo "        <TD align=\"center\"".$bgCoul."><A href=\"?lettre=".chr($i)."&sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."\" class=\"alphabet\"><FONT color=\"".$fontCoul."\"><B>".chr($i)."</B></FONT></A></TD>\n";
    }
    echo "      </TR>\n";
    echo "    </TABLE>";
  }
//--------------------------------------------------


// Affiche la barre de recherche
  function aff_rech() {
    global $rech_txt, $sur;
    echo ("<TABLE align=\"center\">
      <TR>
        <TD align=\"left\" nowrap>".trad("CALEPIN_LIB_CHERCHER")."&nbsp;</TD>
        <TD align=\"left\" nowrap><INPUT type=\"text\" class=\"texte\" name=\"rech_txt\" value=\"".$rech_txt."\"></TD>
        <TD align=\"left\" nowrap>&nbsp;".trad("CALEPIN_DANS")."&nbsp;</TD>
        <TD align=\"left\" nowrap><SELECT name=\"sur\">
          <OPTION value=\"tous\"".(($sur=="tous") ? " selected" : "").">".trad("CALEPIN_CHERCHER_TOUS")."</OPTION>
          <OPTION value=\"soc\"".(($sur=="soc") ? " selected" : "").">".trad("CALEPIN_CHERCHER_SOCIETE")."</OPTION>
          <OPTION value=\"np\"".(($sur=="np") ? " selected" : "").">".trad("CALEPIN_CHERCHER_NOM")."</OPTION>
          <OPTION value=\"add\"".(($sur=="add") ? " selected" : "").">".trad("CALEPIN_CHERCHER_ADRESSE")."</OPTION>
          <OPTION value=\"tel\"".(($sur=="tel") ? " selected" : "").">".trad("CALEPIN_CHERCHER_TELEPHONE")."</OPTION>
          <OPTION value=\"mail\"".(($sur=="mail") ? " selected" : "").">".trad("CALEPIN_CHERCHER_EMAIL")."</OPTION>
          <OPTION value=\"divers\"".(($sur=="divers") ? " selected" : "").">".trad("CALEPIN_CHERCHER_COMMENTAIRE")."</OPTION>
        </SELECT></TD>
        <TD align=\"left\" nowrap>&nbsp;<INPUT type=\"button\" class=\"PickList\" name=\"btRecherche\" value=\"".trad("CALEPIN_BT_OK")."\" title=\"".trad("CALEPIN_RECHERCHER")."\" onclick=\"javascript: document.FormRecherche.submit();\"></TD>
      </TR>
    </TABLE>");
  }
//--------------------------------------------------


// Affiche la selection des groupes de contacts
  function aff_groupe($grpPere,$nivGrp, $fils, $grp) {
    global $DB_CX, $PREFIX_TABLE, $idUser, $CalFond;
    $DB = new Db($DB_CX->ConnexionID);
    $DB->DbQuery("SELECT cgr_id, cgr_nom FROM ${PREFIX_TABLE}calepin_groupe WHERE cgr_pere_id=".$grpPere." AND cgr_util_id=".$idUser." ORDER BY cgr_nom");
    $nivGrp++;
    while ($enr = $DB->DbNextRow()) {
      if ($grp == $enr['cgr_id']) {
        $selected = " selected";
        $fils = true;
      }
      else
        $selected = "";
      //Mise en surbrillance du groupe selectionne et de ses fils
      $style = ($fils) ? " style=\"background:".$CalFond.";\"" : "";
      echo "      <OPTION value=\"".$enr['cgr_id']."\"".$selected.$style.">";
      for ($i=0;$i<$nivGrp-1;$i++)
        echo "&bull;&nbsp;";//&rsaquo;
      echo $enr['cgr_nom']."</OPTION>\n";
      aff_groupe($enr['cgr_id'],$nivGrp,$fils, $grp);
      //Fin de la surbrillance
      if ($selected == " selected")
        $fils = false;
    }
  }
//--------------------------------------------------


// Affiche le formulaire pour saisir ou modifier un contact
  function aff_nouveau($err = "") {
    global $societe,$nom,$prenom,$add,$cp,$ville,$pays,$domicile,$travail,$portable,$fax,$email,$icq,$groupe,$proprio,$partage,$note,$aim,$msn,$yahoo,$naissance,$emailpro,$siteweb;
    global $type2,$id,$sid,$idUser,$sd,$tcMenu,$tcPlg,$bgColor,$CalepinFondMessage,$AgendaBordureTableau;
    if ($err != "") {
      echo "  <TR bgcolor=\"".$CalepinFondMessage."\">\n      <TD class=\"bordTLRB\" align=\"center\"><P class=\"rouge\" style=\"text-align:left;\">".$err."</P></TD>\n    </TR>\n";
    }
    echo "  <TR>\n    <TD align=\"center\"><FORM name=\"frmCalepin\" method=\"POST\" action=\"?sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."\">\n";
    if (!$id)
      $proprio = $idUser;
    else
      echo "      <INPUT type=\"hidden\" name=\"proprio\" value=\"".$proprio."\">\n";
    if ($type2=="modif") {
      echo "      <INPUT type=\"hidden\" name=\"type2\" value=\"modif\">\n";
      echo "      <INPUT type=\"hidden\" name=\"id\" value=\"".$id."\">\n";
      if ($idUser!=$proprio) {
        echo "      <INPUT type=\"hidden\" name=\"groupe\" value=\"".$groupe."\">\n";
        echo "      <INPUT type=\"hidden\" name=\"partage\" value=\"O\">\n";
      }
      $labelBouton = trad("CALEPIN_BT_MODIFIER");
    }
    else {
      echo "      <INPUT type=\"hidden\" name=\"ztAction\" value=\"\">\n";
      $labelBouton = trad("CALEPIN_BT_AJOUTER");
    }
    $index = 0;
    echo ("      <TABLE cellspacing=\"0\" cellpadding=\"0\" border=\"0\" width=\"100%\">
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\" width=\"100\">".trad("CALEPIN_LIB_SOCIETE")."</TD>
        <TD class=\"tabInput\" width=\"350\"><INPUT type=\"text\" class=\"texte\" name=\"societe\" value=\"".htmlspecialchars(stripslashes($societe))."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_NOM")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"nom\" value=\"".htmlspecialchars(stripslashes($nom))."\" size=40 maxlength=50 style=\"text-transform: uppercase;\"></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_PRENOM")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"prenom\" value=\"".htmlspecialchars(stripslashes($prenom))."\" size=40 maxlength=30 style=\"text-transform: capitalize;\"></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_ADRESSE")."</TD>
        <TD class=\"tabInput\"><TEXTAREA name=\"add\" cols=\"52\" rows=\"4\" wrap=\"soft\" style=\"width:469px;\">".htmlspecialchars(stripslashes($add))."</TEXTAREA></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_CP")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"cp\" value=\"".htmlspecialchars(stripslashes($cp))."\" size=6 maxlength=10></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_VILLE")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"ville\" value=\"".htmlspecialchars(stripslashes($ville))."\" size=40 maxlength=100></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_PAYS")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"pays\" value=\"".htmlspecialchars(stripslashes($pays))."\" size=40 maxlength=100></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_TEL_DOMICILE")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"domicile\" value=\"".$domicile."\" size=15 maxlength=20></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_TEL_TRAVAIL")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"travail\" value=\"".$travail."\" size=15 maxlength=20></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\" nowrap>".trad("CALEPIN_LIB_TEL_PORTABLE")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"portable\" value=\"".$portable."\" size=15 maxlength=20></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\" nowrap>".trad("CALEPIN_LIB_FAX")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"fax\" value=\"".$fax."\" size=15 maxlength=20></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_EMAIL")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"email\" value=\"".$email."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_EMAIL_PRO")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"emailpro\" value=\"".$emailpro."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_SITE_WEB")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"siteweb\" value=\"".$siteweb."\" style=\"width:468px\" maxlength=255></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_ICQ")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"icq\" value=\"".(($icq>0)?$icq:"")."\" size=12 maxlength=15></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_AIM")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"aim\" value=\"".$aim."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_MSN")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"msn\" value=\"".$msn."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_YAHOO")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"yahoo\" value=\"".$yahoo."\" size=40 maxlength=50></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\" nowrap>".trad("CALEPIN_LIB_DATE_NAISSANCE")."</TD>
        <TD class=\"tabInput\"><INPUT type=\"text\" class=\"texte\" name=\"naissance\" id=\"naissance\" value=\"".$naissance."\" size=12 maxlength=10 title=\"".trad("CALEPIN_FORMAT_DATE")."\" onKeyPress=\"return onlyChar(event);\">&nbsp;<INPUT type=\"button\" id=\"btCal\" value=\"...\" class=\"Picklist\" style=\"height:16px\" title=\"".trad("CALEPIN_AFFICHE_CALENDRIER")."\">&nbsp;&nbsp;<I>(".trad("CALEPIN_FORMAT_DATE").")</I></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_DIVERS")."</TD>
        <TD class=\"tabInput\">");
    genereTextArea("note",$note,469,7);
    echo ("</TD>
      </TR>\n");
    if ($idUser==$proprio) {
      echo ("      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_GROUPE")."</TD>
        <TD class=\"tabInput\"><SELECT name=\"groupe\" size=\"1\" onChange=\"javascript: changeLabel(document.frmCalepin);\">\n          <OPTION value=\"0\">".trad("CALEPIN_NOUVEAU_GROUPE")."</OPTION>\n");
      aff_groupe(0,0,false,$groupe);
      echo ("        </SELECT>&nbsp;&nbsp;<INPUT type=\"button\" class=\"bouton\" name=\"btAjouter\" value=\"".$labelBouton."\" onclick=\"javascript: ajoutGrp(document.frmCalepin);\"></TD>
      </TR>
      <TR bgcolor=\"".$bgColor[(++$index)%2]."\" height=\"21\">
        <TD class=\"tabIntitule\">".trad("CALEPIN_LIB_PARTAGE")."</TD>
        <TD class=\"tabInput\"><LABEL for=\"partageCtt\"><INPUT type=\"checkbox\" name=\"partage\" id=\"partageCtt\" value=\"O\" class=\"Case\"".(($partage=="O")?" checked":"").">&nbsp;".trad("CALEPIN_COCHER_PARTAGE")."</LABEL></TD>
      </TR>\n");
    }
    echo ("      </TABLE>
      <INPUT type=\"hidden\" name=\"type\" value=\"\">
      <BR><INPUT type=\"button\" class=\"bouton\" value=\"".trad("CALEPIN_BT_ENREGISTRER")."\" onClick=\"javascript: return saisieOK(document.frmCalepin);\">&nbsp;&nbsp;&nbsp;");
      if ($type2!="modif") {
        echo "<INPUT type=\"button\" name=\"btRecommence\" value=\"".trad("CALEPIN_BT_RECOMMENCER")."\" onClick=\"javascript: recommence(document.frmCalepin);\" class=\"Bouton\">&nbsp;&nbsp;&nbsp;";
      }
      echo ("<INPUT type=\"button\" class=\"bouton\" name=\"btAnnule\" value=\"".trad("CALEPIN_BT_ANNULER")."\" onclick=\"javascript: btAnnul();\">
    </FORM>
    <SCRIPT type=\"text/javascript\">
    <!--
      Calendar.setup( {
        inputField : \"naissance\",    // ID of the input field
        ifFormat   : \"%d/%m/%Y\",  // the date format
        button     : \"btCal\"      // ID of the button
      } );\n");
    if (!$id)
      echo("      document.frmCalepin.societe.focus();\n");
    echo("    //-->
    </SCRIPT></TD>\n  </TR>\n");
  }
//--------------------------------------------------


//Fonction recursive permettant d'obtenir les groupes fils de celui correspondant a $id
  function grpFils($id,&$listeGrp) {
    global $DB_CX, $PREFIX_TABLE, $idUser;
    $listeGrp .= $id.",";
    $DB = new Db($DB_CX->ConnexionID);
    $DB->DbQuery("SELECT cgr_id FROM ${PREFIX_TABLE}calepin_groupe WHERE cgr_pere_id=".$id." AND cgr_util_id=".$idUser);
    while ($enr = $DB->DbNextRow()) {
      grpFils($enr['cgr_id'],$listeGrp);
    }
  }
//--------------------------------------------------


//Recherche <$rech_txt> selon le type de la recherche <$sur> (tout,nom+prenom,adresse,telephone et portable)
  function aff_res($sur,$rech_txt) {
    global $DB_CX, $PREFIX_TABLE, $MODIF_PARTAGE, $idUser, $sid, $sd, $tcMenu, $tcPlg, $bgColor, $type, $lettre, $CalepinFondMessage;
    if ($rech_txt == "") {
      if ($sur == "soc")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_SOCIETE")."&nbsp;";
      elseif ($sur == "np")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_NOM")."&nbsp;";
      elseif ($sur == "add")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_ADRESSE")."&nbsp;";
      elseif ($sur == "tel")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_TELEPHONE")."&nbsp;";
      elseif ($sur == "mail")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_EMAIL")."&nbsp;";
      elseif ($sur == "divers")
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_COMMENTAIRE")."&nbsp;";
      else
        $msg_err = "&nbsp;".trad("CALEPIN_RECH_SAISIR_CRITERE")."&nbsp;";
      $sur = "";
    }

    if ($sur == "id") {
      // Recherche a partir de l'identifiant d'un contact associe a une note
      $sql = "WHERE (cal_id=".$rech_txt;
    }

    elseif ($sur == "nom") {
      // Recherche sur la premiere lettre du nom (Barre alphabet en haut)
      $rech_txt = strtolower($rech_txt);
      $sql = "WHERE (LOWER(cal_nom) LIKE LOWER('".$rech_txt."%')";
    }

    elseif ($sur == "groupe") {
      // Recherche par le groupe d'appartenance
      // On verifie si on affiche le groupe des partages
      if ($rech_txt == 100000000)
        $sqlPartage = "SELECT ${PREFIX_TABLE}calepin.*, 100000000, 'Partage' FROM ${PREFIX_TABLE}calepin WHERE cal_partage='O' ORDER BY cal_nom ASC, cal_prenom ASC, cal_societe ASC";
      else {
        // Recuperation des groupes fils de celui selectionne
        grpFils($rech_txt,$listeGrp);
        $sql = "WHERE (cap_cgr_id IN (".substr ($listeGrp,0,-1).")";
      }
    }

    elseif ($sur == "tous")  {
      // Req. sur tout
      $tous = true;
      $sql = "WHERE (";
      $rech_txt = explode(" ",$rech_txt);
    } else {
      $tous = false;
    }

    if ($sur == "soc" || $tous)  {
      // Req. sur la societe.
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = ($i>0) ? "OR " : "";
        $sql .= $or."LOWER(cal_societe) LIKE LOWER('%".$rech_txt[$i]."%') ";
      }
    }

    if ($sur == "np" || $tous)  {
      // Req. sur le nom et le prenom.
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = (($i>0) || $tous) ? "OR " : "";
        $sql .= $or."LOWER(cal_nom) LIKE LOWER('%".$rech_txt[$i]."%') ";
        $sql .= "OR LOWER(cal_prenom) LIKE LOWER('%".$rech_txt[$i]."%') ";
      }
    }

    if ($sur == "add" || $tous) {
      // Req. sur l'adresse.
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = (($i>0) || $tous) ? "OR " : "";
        $sql .= $or."LOWER(cal_adresse) LIKE LOWER('%".$rech_txt[$i]."%') ";
        $sql .= "OR cal_cp='".$rech_txt[$i]."' ";
        $sql .= "OR LOWER(cal_ville) LIKE LOWER('%".$rech_txt[$i]."%') ";
        $sql .= "OR LOWER(cal_pays) LIKE LOWER('%".$rech_txt[$i]."%') ";
      }
    }

    if ($sur == "tel" || $tous) {
      // Req. sur les nums. de tel
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = (($i>0) || $tous) ? "OR " : "";
        $sql .= $or."cal_domicile='".$rech_txt[$i]."' ";
        $sql .= "OR cal_travail='".$rech_txt[$i]."' ";
        $sql .= "OR cal_portable='".$rech_txt[$i]."' ";
        $sql .= "OR cal_fax='".$rech_txt[$i]."' ";
      }
    }

    if ($sur == "mail" || $tous) {
      // Req. sur les Email
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = (($i>0) || $tous) ? "OR " : "";
        $sql .= $or."LOWER(cal_email) LIKE LOWER('%".$rech_txt[$i]."%') OR LOWER(cal_emailpro) LIKE LOWER('%".$rech_txt[$i]."%') ";
      }
    }

    if ($sur == "divers" || $tous) {
      // Req. sur les Commentaires
      if (!$tous) {
        $sql = "WHERE (";
        $rech_txt = explode(" ",$rech_txt);
      }
      for ($i=0;$i<count($rech_txt);$i++) {
        $or = (($i>0) || $tous) ? "OR " : "";
        $sql .= $or."LOWER(cal_note) LIKE LOWER('%".$rech_txt[$i]."%') ";
      }
    }

    if ($sqlPartage != "")
      $sql = $sqlPartage;
    elseif ($sql != "")
      $sql = "SELECT ${PREFIX_TABLE}calepin.*, cgr_id, cgr_nom FROM ${PREFIX_TABLE}calepin, ${PREFIX_TABLE}calepin_appartient, ${PREFIX_TABLE}calepin_groupe ".$sql.") AND ((cal_util_id=".$idUser." AND cap_cal_id=cal_id AND cgr_id=cap_cgr_id) OR (cal_util_id!=".$idUser." AND cal_partage='O' AND cap_cal_id=cal_id AND cgr_id=cap_cgr_id)) ORDER BY cal_nom ASC, cal_prenom ASC, cal_societe ASC";

    if ($sql != "") {
      $DB_CX->DbQuery($sql);
      $nb = $DB_CX->DbNumRows();
      $pluriel = ($nb > 1) ? trad("CALEPIN_PLURIEL") : "";
      if ($lettre != "")
        $critere = sprintf(trad("CALEPIN_TROUVE_LETTRE"), $pluriel, $lettre);
      elseif ($sur == "groupe") {
        //Nom du groupe selectionne
        if ($rech_txt != 100000000) {
          $DB = new Db($DB_CX->ConnexionID);
          $DB->DbQuery("SELECT cgr_nom FROM ${PREFIX_TABLE}calepin_groupe WHERE cgr_id=".$rech_txt);
          $critere = sprintf(trad("CALEPIN_TROUVE_GROUPE"), $pluriel, $DB->DbResult(0,0));
        }
        else
          $critere = sprintf(trad("CALEPIN_PARTAGE_UTILS"), $pluriel);
      }
      else
        $critere = (($nb == 0) ? trad("CALEPIN_NEGATION")." " : "").trad("CALEPIN_CORRESPOND_CRITERE");
      $strOutput = "";
      if ($nb == 0)
        $strOutput .= "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLRB\" align=\"center\" height=\"22\"><P class=\"rouge\">".trad("CALEPIN_AUCUN_CONTACT")." ".$critere."</P></TD>\n  </TR>\n";
      else {
        // Resultat de la recherche
        $strOutput .= "  <TR bgcolor=\"".$CalepinFondMessage."\">
    <TD class=\"bordTLRB\" width=\"100%\"><FORM name=\"expfic\"><TABLE cellspacing=\"0\" cellpadding=\"2\" border=\"0\">
      <TR>
        <TD><INPUT type=\"button\" class=\"bouton\" name=\"btExporter\" value=\"".trad("CALEPIN_BT_EXPORTER")."\" title=\"".trad("CALEPIN_BT_EXPORTER")."\" style=\"width:65px;\" onclick=\"javascript: document.location.href='agenda_calepin_export.php?sid=".$sid."&sql=".addslashes($sql)."&fictype='+document.forms.expfic.zlExp.options[document.forms.expfic.zlExp.selectedIndex].value+'';\"></TD>
        <TD><SELECT name=\"zlExp\" size=\"1\">
          <OPTION value=\"vcard\" selected>".trad("CALEPIN_EXPORT_VCARD_STD")."</OPTION>
          <OPTION value=\"vcard-palm\">".trad("CALEPIN_EXPORT_VCARD_PALM")."</OPTION>
          <OPTION value=\"csvv\">".trad("CALEPIN_EXPORT_CSV_V")."</OPTION>
          <OPTION value=\"csvpv\">".trad("CALEPIN_EXPORT_CSV_PV")."</OPTION>
          <OPTION value=\"ldif\">".trad("CALEPIN_EXPORT_LDIF")."</OPTION>
        </SELECT></TD>
        <TD align=\"center\" width=\"100%\" nowrap><A class=\"vert\">".sprintf(trad("CALEPIN_CONTACT"), $nb, $pluriel)." ".$critere."</A></TD><TD><INPUT type=\"button\" class=\"bouton\" name=\"btImprimer\" value=\"".trad("CALEPIN_BT_IMPRIMER")."\" title=\"".trad("CALEPIN_BT_IMPRIMER")."\" style=\"width:65px;\" onclick=\"javascript: parent.imprime('".$tcMenu."','".$sd."','".addslashes($sql)."');\"></TD>
      </TR>
    </TABLE></FORM></TD>\n";
        $strOutput .= "  </TR>\n";
        $strOutput .= "  <TR>\n";
        $strOutput .= "    <TD><FORM><TABLE width=\"650\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";

        $index = 0;
        while ($enr = $DB_CX->DbNextRow()) {
          $index = 1 - $index;
          if ($enr['cal_util_id'] == $idUser) {
            $grpWidth =  210;
            $colspan = 1;
          } else {
            if ($MODIF_PARTAGE) {
              $grpWidth = 210;
              $colspan = 1;
            } else {
              $grpWidth = 250;
              $colspan = 2;
            }
            // On rattache les contacts au groupe de partage (fictif)
            $enr['cgr_id'] = 100000000;
            $enr['cgr_nom'] = trad("CALEPIN_LIB_PARTAGE");
          }
          if (!empty($enr['cal_note'])) { //Commentaire
            $lCommentaire  = "      <TR bgcolor=\"".$bgColor[$index]."\">\n        <TD width=\"100%\" colspan=\"".(2+$colspan)."\">";
            $lCommentaire .= "&nbsp;<BR><U>".trad("CALEPIN_COMMENTAIRE")."</U> :<BR>".nlTObr($enr['cal_note'])."</TD>\n      </TR>\n";
            $rowspan="3";
          } else {
            $lCommentaire="";
            $rowspan="2";
          }
          $strOutput .= "      <TR bgcolor=\"".$bgColor[$index]."\">\n";
          $strOutput .= "        <TD width=\"3\" rowspan=\"".$rowspan."\" class=\"bordL\"><IMG src=\"image/trans.gif\" width=\"3\" height=\"1\" alt=\"\" border=\"0\"></TD>\n";
          $strOutput .= "        <TD width=\"217\" valign=\"middle\"><I>".$enr['cal_societe']."</I>&nbsp;</TD>\n";
          $strOutput .= "        <TD width=\"130\" valign=\"middle\"><A href=\"?sid=$sid&amp;tcMenu="._MENU_RECHERCHE."&amp;tcPlg=$tcPlg&amp;sd=$sd&amp;zlContactAssocie=".$enr['cal_id']."\"><IMG src=\"image/recherche_note.gif\" border=0 width=15 height=15 title=\"".trad("CALEPIN_AFFICHE_NOTES")."\" align=\"absmiddle\"></A>&nbsp;&nbsp;<A href=\"?sid=$sid&amp;tcPlg=$tcPlg&amp;sd=$sd&amp;tcType="._TYPE_NOTE."&amp;cA=".$enr['cal_id']."\"><IMG src=\"image/ajout_note.gif\" border=0 width=13 height=15 title=\"".trad("CALEPIN_AJOUT_NOTE")."\" align=\"absmiddle\"></A></TD>\n";
          $strOutput .= "        <TD width=\"".$grpWidth."\" valign=\"top\" colspan=\"".$colspan."\"><IMG src=\"image/calepin/groupe.gif\" border=0 width=16 height=16 vspace=1 title=\"".trad("CALEPIN_AFFECTE_GROUPE")."\" align=\"absmiddle\">&nbsp;<A href=\"?sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&grp=".$enr['cgr_id']."\">".$enr['cgr_nom']."</A></TD>\n";
          if ($enr['cal_util_id']==$idUser || $MODIF_PARTAGE) { // Modif du contact
            $strOutput .= "        <TD width=\"40\" rowspan=\"".$rowspan."\" nowrap><INPUT type=\"button\" class=\"bouton\" name=\"btModif\" value=\"".trad("CALEPIN_BT_M")."\" title=\"".trad("CALEPIN_BT_MODIFIER")."\" style=\"width: 15px;\" onclick=\"javascript: window.location.href='?ztAction=M&id=".$enr['cal_id']."&sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."';\">";
            if ($enr['cal_util_id']==$idUser) // Suppression du contact
              $strOutput .= "&nbsp;<INPUT type=\"button\" class=\"bouton\" name=\"btSuppr\" value=\"".trad("CALEPIN_BT_S")."\" title=\"".trad("CALEPIN_BT_SUPPRIMER")."\" style=\"width: 15px;\" onclick=\"javascript: if (confirm('".trad("CALEPIN_JS_CONFIRME_SUPPRIMER")."')) window.location.href='?ztAction=S&id=".$enr['cal_id']."&sid=".$sid."&tcMenu=".$tcMenu."&tcPlg=".$tcPlg."&sd=".$sd."&lettre=".substr($enr['cal_nom'],0,1)."';\">";
            $strOutput .= "</TD>\n";
          }
          $strOutput .= "        <TD width=\"3\" rowspan=\"".$rowspan."\" class=\"bordR\"><IMG src=\"image/trans.gif\" width=\"3\" height=\"1\" alt=\"\" border=\"0\"></TD>\n";
          $strOutput .= "      </TR>\n      <TR bgcolor=\"".$bgColor[$index]."\">\n        <TD width=\"217\" valign=\"top\">";
          if (!empty($enr['cal_nom']) || !empty($enr['cal_prenom']))  // Nom et Prenom
            $strOutput .= "<B>".trim($enr['cal_nom']." ".$enr['cal_prenom'])."</B>&nbsp;&nbsp;<BR>";
          if (!empty($enr['cal_adresse']))   // Adresse
            $strOutput .= nlTObr($enr['cal_adresse'],"&nbsp;&nbsp;")."&nbsp;&nbsp;<BR>";
          if (!empty($enr['cal_cp']) || !empty($enr['cal_ville']))  // Code postal et Ville
            $strOutput .= "<BR>".trim($enr['cal_cp']." ".$enr['cal_ville'])."&nbsp;&nbsp;";
          if (!empty($enr['cal_pays']))   // Pays
            $strOutput .= "<BR>".$enr['cal_pays']."&nbsp;&nbsp;";
          if (!empty($enr['cal_date_naissance']) && $enr['cal_date_naissance']!="0000-00-00") { // Age
            $tabDate = explode("-",$enr['cal_date_naissance']);
            $age = calculAge($tabDate,$sd);
            $pluriel = ($age>1) ? trad("COMMUN_PLURIEL") : "";
            $strOutput .= "<BR><BR>".(($age>0) ? sprintf(trad("COMMUN_AGE"),$age,$pluriel,$tabDate[0],$tabDate[1],$tabDate[2]) : trad("COMMUN_JOUR_NAISSANCE"))."&nbsp;&nbsp;";
          }
          $strOutput .= "</TD>\n        <TD width=\"130\" valign=\"top\">";
          if (!empty($enr['cal_domicile']))   // Telephone domicile
            $strOutput .= "<IMG src=\"image/calepin/telephone.gif\" border=0 width=18 height=14 vspace=1 title=\"".trad("CALEPIN_DOMICILE")."\" align=\"absmiddle\"> ".telephoneVF($enr['cal_domicile'])."<BR>";
          if (!empty($enr['cal_travail']))   // Telephone professionnel
            $strOutput .= "<IMG src=\"image/calepin/telephone2.gif\" border=0 width=18 height=14 vspace=1 title=\"".trad("CALEPIN_TRAVAIL")."\" align=\"absmiddle\"> ".telephoneVF($enr['cal_travail'])."<BR>";
          if (!empty($enr['cal_portable']))  // Portable
            $strOutput .= "<IMG src=\"image/calepin/portable.gif\" border=0 width=18 height=16 vspace=1 title=\"".trad("CALEPIN_PORTABLE")."\" align=\"absmiddle\"> ".telephoneVF($enr['cal_portable'])."<BR>";
          if (!empty($enr['cal_fax']))  // Fax
            $strOutput .= "<IMG src=\"image/calepin/fax.gif\" border=0 width=16 height=15 vspace=1 hspace=1 title=\"".trad("CALEPIN_LIB_FAX")."\" align=\"absmiddle\"> ".telephoneVF($enr['cal_fax']);
          $strOutput .= "</TD>\n        <TD width=\"".$grpWidth."\" valign=\"top\" colspan=\"".$colspan."\">";
          if (!empty($enr['cal_email']))  // Adresse Email
            $strOutput .= "<IMG src=\"image/calepin/email.gif\" border=0 width=18 height=16 vspace=1 title=\"".trad("CALEPIN_LIB_EMAIL")."\" align=\"absmiddle\"> <A href=\"mailto:".$enr['cal_email']."\">".$enr['cal_email']."</A><BR>";
          if (!empty($enr['cal_emailpro']))  // Adresse Email Professionnelle
            $strOutput .= "<IMG src=\"image/calepin/email.gif\" border=0 width=18 height=16 vspace=1 title=\"".trad("CALEPIN_LIB_EMAIL_PRO")."\" align=\"absmiddle\"> <A href=\"mailto:".$enr['cal_emailpro']."\">".$enr['cal_emailpro']."</A><BR>";
          if (!empty($enr['cal_siteweb']) && $enr['cal_siteweb']!="http://")  // Site Web
            $strOutput .= "<IMG src=\"image/calepin/site.gif\" border=0 width=15 height=15 vspace=1 hspace=1 title=\"".trad("CALEPIN_LIB_SITE_WEB")."\" align=\"absmiddle\"> <A href=\"".$enr['cal_siteweb']."\" target=\"_blank\">".trad("CALEPIN_LIB_SITE_WEB")."</A><BR>";
          if (!empty($enr['cal_icq']))  // ICQ
            $strOutput .= "<IMG src=\"http://web.icq.com/whitepages/online?icq=".$enr['cal_icq']."&img=5\" border=0 title=\"".trad("CALEPIN_LIB_ICQ")."\" align=\"absmiddle\" onerror=\"javascript: this.onerror=null;this.src='image/calepin/icq.gif';\">&nbsp;".$enr['cal_icq']."<BR>";
          if (!empty($enr['cal_aim']))  // AIM
            $strOutput .= "<IMG src=\"image/calepin/aim.gif\" border=0 vspace=1 hspace=1 title=\"".trad("CALEPIN_LIB_AIM")."\" align=\"absmiddle\">&nbsp;".$enr['cal_aim']."<BR>";
          if (!empty($enr['cal_msn']))  // MSN
            $strOutput .= "<IMG src=\"image/calepin/msn.gif\" border=0 vspace=1 hspace=1 title=\"".trad("CALEPIN_LIB_MSN")."\" align=\"absmiddle\">&nbsp;".$enr['cal_msn']."<BR>";
          if (!empty($enr['cal_yahoo']))  // YAHOO
            $strOutput .= "<IMG src=\"image/calepin/yahoo.gif\" border=0 vspace=1 hspace=1 title=\"".trad("CALEPIN_LIB_YAHOO")."\" align=\"absmiddle\">&nbsp;".$enr['cal_yahoo'];
          $strOutput .= "</TD>\n      </TR>\n";
          $strOutput .= $lCommentaire;
          $strOutput .= "      <TR bgcolor=\"".$bgColor[$index]."\">\n        <TD height=\"1\" colspan=\"6\" class=\"bordLRB\"><IMG src=\"image/trans.gif\" height=\"1\" alt=\"\" border=\"0\"></TD>\n      </TR>\n";
        }
        $strOutput .= "    </TABLE></FORM></TD>\n  </TR>\n";
      }
      echo $strOutput;
    }
    elseif ($msg_err != "")
      echo "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLRB\" align=\"center\"><P class=\"rouge\">".$msg_err."</P></TD>\n  </TR>\n";
  }
//--------------------------------------------------


//Efface les donnees saisies dans le formulaire lorsque l'on revient sur la page de creation d'un contact via le bouton Recommencer
function resetForm() {
    global $societe,$nom,$prenom,$add,$cp,$ville,$pays,$domicile,$travail,$portable,$fax,$email,$icq,$groupe,$proprio,$partage,$note,$aim,$msn,$yahoo,$naissance,$emailpro,$lettre,$err,$siteweb;
    $societe = $nom = $prenom = $add = $cp = $ville = $pays = $domicile = "";
    $travail = $portable = $fax = $email = $emailpro = $groupe = $proprio = "";
    $partage = $note = $naissance = $icq = $aim = $msn = $yahoo = $siteweb = "";
    $err = "";
}
//--------------------------------------------------


// Verifie si les donnees sont correctes et renvoie vrai ou faux. ($err contient l'erreur)
function verif($nom,&$domicile,&$travail,&$portable,&$fax,$email,$emailpro,$icq,$groupe,&$err) {
  global $TELEPHONE_VF;
  // Nom et numero de telephone ou email obligatoire.
  if ($nom == "")
    $err .= "&nbsp;- ".trad("CALEPIN_SAISIR_NOM")."<BR>";

  if ($TELEPHONE_VF) {
    // Les numeros de telephones ne doivent contenir que des chiffres.
    $domicile = preg_replace( "/[^0-9+]+/","",$domicile);
    $travail  = preg_replace( "/[^0-9+]+/","",$travail);
    $portable = preg_replace( "/[^0-9+]+/","",$portable);
    $fax      = preg_replace( "/[^0-9+]+/","",$fax);
  }

  if (($email != "") && (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$",$email)))
    $err .= "&nbsp;- ".trad("CALEPIN_SAISIR_EMAIL");

  if (($emailpro != "") && (!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)+$",$emailpro)))
    $err .= "&nbsp;- ".trad("CALEPIN_SAISIR_EMAIL_PRO");

  if (($icq != "") && (!ereg("^[0-9]*$",$icq)))
    $err .= "&nbsp;- ".trad("CALEPIN_SAISIR_ICQ")."<BR>";

  if ($groupe == "0")
    $err .= "&nbsp;- ".trad("CALEPIN_SAISIR_GROUPE")."<BR>";

  return ($err == "");
}
//--------------------------------------------------


  if ($ztAction == "S" && $id)  {
    //Suppression d'une entree
    if ($DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}calepin WHERE cal_id=".$id.(($MODIF_PARTAGE) ? "" : " AND cal_util_id=".$idUser)) && $DB_CX->DbAffectedRows()>0) {
      $msg_maj = "<P class=\"vert\">".trad("CALEPIN_EFFACE_OK")."</P>";
      $DB_CX->DbQuery("DELETE FROM ${PREFIX_TABLE}calepin_appartient WHERE cap_cal_id=".$id);
    } else
      $msg_maj = "<P class=\"rouge\">".trad("CALEPIN_SUPPR_IMPOSSIBLE")."</P>";
  } elseif ($ztAction == "M") {
    //Modification d'une entree
    if (isset($ztNom) && !empty($ztNom)) {
      //Enregistrement d'un groupe depuis le popup
      if ($groupe != "0") {
        //UPDATE
        $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}calepin_groupe SET cgr_pere_id=".$zlPere.", cgr_nom='".htmlspecialchars($ztNom)."' WHERE cgr_id=".$groupe);
      } else {
        //INSERT
        $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}calepin_groupe (cgr_pere_id, cgr_util_id, cgr_nom) VALUES (".$zlPere.", ".$idUser.", '".htmlspecialchars($ztNom)."')");
        $groupe = $DB_CX->DbInsertID();
      }
      //Recuperation des info deja saisies
      $societe   = htmlspecialchars(stripslashes($societe));
      $nom       = htmlspecialchars(stripslashes($nom));
      $prenom    = htmlspecialchars(stripslashes($prenom));
      $add       = htmlspecialchars(stripslashes($add));
      $cp        = htmlspecialchars(stripslashes($cp));
      $ville     = htmlspecialchars(stripslashes($ville));
      $domicile  = htmlspecialchars(stripslashes($domicile));
      $travail   = htmlspecialchars(stripslashes($travail));
      $portable  = htmlspecialchars(stripslashes($portable));
      $fax       = htmlspecialchars(stripslashes($fax));
      $email     = htmlspecialchars(stripslashes($email));
      $emailpro  = htmlspecialchars(stripslashes($emailpro));
      $icq       = htmlspecialchars(stripslashes($icq));
      $aim       = htmlspecialchars(stripslashes($aim));
      $msn       = htmlspecialchars(stripslashes($msn));
      $yahoo     = htmlspecialchars(stripslashes($yahoo));
      $naissance = htmlspecialchars(stripslashes($naissance));
      $note      = ($AUTORISE_HTML) ? stripslashes($note) : htmlspecialchars(stripslashes($note));
      $siteweb   = htmlspecialchars(stripslashes($siteweb));
    } else {
      //Recuperation des info dans la bdd
      $DB_CX->DbQuery("SELECT ${PREFIX_TABLE}calepin.*, cap_cgr_id FROM ${PREFIX_TABLE}calepin, ${PREFIX_TABLE}calepin_appartient WHERE cal_id=".$id.(($MODIF_PARTAGE) ? "" : " AND cal_util_id=".$idUser)." AND cap_cal_id=cal_id");
      $enr      = $DB_CX->DbNextRow();
      $societe  = $enr['cal_societe'];
      $nom      = $enr['cal_nom'];
      $prenom   = $enr['cal_prenom'];
      $add      = $enr['cal_adresse'];
      $cp       = $enr['cal_cp'];
      $ville    = $enr['cal_ville'];
      $pays     = $enr['cal_pays'];
      $domicile = $enr['cal_domicile'];
      $travail  = $enr['cal_travail'];
      $portable = $enr['cal_portable'];
      $fax      = $enr['cal_fax'];
      $email    = $enr['cal_email'];
      $icq      = $enr['cal_icq'];
      $proprio  = $enr['cal_util_id'];
      $partage  = $enr['cal_partage'];
      $note     = $enr['cal_note'];
      $aim      = $enr['cal_aim'];
      $msn      = $enr['cal_msn'];
      $yahoo    = $enr['cal_yahoo'];
      $emailpro = $enr['cal_emailpro'];
      $siteweb  = $enr['cal_siteweb'];
      $groupe   = $enr['cap_cgr_id'];
      $type2    = "modif";
      if (!empty($enr['cal_date_naissance']) && $enr['cal_date_naissance']!="0000-00-00") {
        $tabDate = explode("-",$enr['cal_date_naissance']);
        $naissance = $tabDate[2]."/".$tabDate[1]."/".$tabDate[0];
      } else
        $naissance = "";
    }
    $type = "Nouveau";
  }

  if ($type == "Enregistrer") {
    // Enregistrement d'une entree
    $societe   = trim($societe);
    $nom       = strtoupper(trim($nom));
    $prenom    = ucwords(strtolower(trim($prenom)));
    $add       = trim($add);
    $cp        = trim($cp);
    $ville     = ucwords(strtolower(trim($ville)));
    $pays      = ucwords(strtolower(trim($pays)));
    $domicile  = trim($domicile);
    $travail   = trim($travail);
    $portable  = trim($portable);
    $fax       = trim($fax);
    $email     = trim($email);
    $emailpro  = trim($emailpro);
    $icq       = trim($icq);
    $aim       = trim($aim);
    $msn       = trim($msn);
    $yahoo     = trim($yahoo);
    $naissance = trim($naissance);
    $note      = trim($note);
    $siteweb   = trim($siteweb);

    if ($partage != "O") $partage = "N";
    if (verif($nom,$domicile,$travail,$portable,$fax,$email,$emailpro,$icq,$groupe,$err)) {
      //Si la date de naissance saisie est erronee, on l'efface
      if (!empty($naissance)) {
        list($D,$M,$Y) = explode("/",$naissance);
        $naissance = (@checkdate($M,$D,$Y)) ? "$Y-$M-$D" : "";
      }
      if (empty($icq)) {
        $icq = 0;
      }
      $lettre = strtoupper($nom[0]);
      if ($type2 == "modif") {
        if ($DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}calepin SET cal_societe='".$societe."', cal_nom='".$nom."', cal_prenom='".$prenom."', cal_adresse='".$add."', cal_cp='".$cp."', cal_ville='".$ville."', cal_pays='".$pays."', cal_domicile='".$domicile."', cal_travail='".$travail."', cal_portable='".$portable."', cal_fax='".$fax."', cal_email='".$email."', cal_emailpro='".$emailpro."', cal_icq=".$icq.", cal_partage='".$partage."', cal_note='".$note."',cal_date_naissance='".$naissance."',cal_aim='".$aim."',cal_msn='".$msn."',cal_yahoo='".$yahoo."',cal_siteweb='".$siteweb."' WHERE cal_id=".$id)) {
          $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}calepin_appartient SET cap_cgr_id=".$groupe." WHERE cap_cal_id=".$id);
          $msg_maj = "<P class=\"vert\">".trad("CALEPIN_MODIF_OK")."</P>";
        } else {
          //En cas d'erreur on reaffiche les infos saisies et un message
          $msg_maj = "<P class=\"rouge\">".trad("CALEPIN_MODIF_KO")."</P>";
          $type = "Nouveau";
          $lettre = "";
        }
      } else {
        // Controle la validite du site web
        if (!empty($siteweb) && (substr($siteweb,0,5)!="http:") && (substr($siteweb,0,6)!="https:") && (substr($siteweb,0,4)!="ftp:")) {
          $siteweb = "http://".$siteweb;
        }
        if ($DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}calepin (cal_societe,cal_nom,cal_prenom,cal_adresse,cal_cp,cal_ville,cal_pays,cal_domicile,cal_travail,cal_portable,cal_fax,cal_email,cal_emailpro,cal_icq,cal_util_id,cal_partage,cal_note,cal_date_naissance,cal_aim,cal_msn,cal_yahoo,cal_siteweb) VALUES ('".$societe."','".$nom."','".$prenom."','".$add."','".$cp."','".$ville."','".$pays."','".$domicile."','".$travail."','".$portable."','".$fax."','".$email."','".$emailpro."',".$icq.",".$idUser.",'".$partage."','".$note."','".$naissance."','".$aim."','".$msn."','".$yahoo."','".$siteweb."')") && $DB_CX->DbAffectedRows()) {
          $DB_CX->DbQuery("INSERT INTO ${PREFIX_TABLE}calepin_appartient (cap_cgr_id, cap_cal_id) VALUES ('".$groupe."','".$DB_CX->DbInsertID()."')");
          $msg_maj = "<P class=\"vert\">".trad("CALEPIN_AJOUT_OK")."</P>";
          //Si pas d'erreur on vide le formulaire si l'utilisateur a choisi de creer un nouveau contact
          if ($ztAction == "R") {
            $type = "Nouveau";
            $lettre = "";
            resetForm();
          }
        } else {
          //En cas d'erreur on reaffiche les infos saisies et un message
          $msg_maj = "<P class=\"rouge\">".trad("CALEPIN_AJOUT_KO")."</P>";
          $type = "Nouveau";
          $lettre = "";
        }
      }
    } else
      $type = "Nouveau";
  }
?>
<!-- MODULE CALEPIN -->
<?php include("inc/checkdate.js.php"); ?>
  <SCRIPT language="JavaScript">
  <!--
    var grpWin;
    function ajoutGrp(theForm) {
      var _width = 320, _height = 120;
      var posX = (Math.max(screen.width,_width)-_width)/2;
      var posY = (Math.max(screen.height,_height)-_height)/2;
      var _position = (navigator.appVersion.match('MSIE')) ? ',top=' + posY + ',left=' + posX : ',screenY=' + posY + ',screenX=' + posX;

      theForm.target = 'ajoutGrp_<?php echo $sid; ?>';
      theForm.action = 'agenda_calepin_groupe.php?sid=<?php echo $sid; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>';
      grpWin = window.open('','ajoutGrp_<?php echo $sid; ?>','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=0,resizable=1,width=' + _width + ',height=' + _height + _position);
      PrepareSave()
      theForm.submit();
    }

    // Change le label du bouton de gestion des groupes
    function changeLabel(theForm) {
      theForm.btAjouter.value = (theForm.groupe.value == "0") ? "<?php echo trad("CALEPIN_BT_AJOUTER");?>" : "<?php echo trad("CALEPIN_BT_MODIFIER");?>";
    }

    //Parametre le retour sur la creation d'un contact et lance l'enregistrement d'un contact
    function recommence(theForm) {
      theForm.ztAction.value = "R";
      if (!saisieOK(theForm)) {
        theForm.ztAction.value = "";
      }
    }

    function saisieOK(theForm) {
      if (chk_date_format(theForm.naissance)) {
        theForm.type.value = 'Enregistrer';
        PrepareSave();
        theForm.submit();
        return (true);
      }
      theForm.type.value = '';
      return (false);
    }
  //-->
  </SCRIPT>
  <SCRIPT language="JavaScript">
  <!--
    function aff(layerID, mode) {
      if (layerID!='init') {
        var currentRef = document.getElementById(layerID).style
        if (layerID!='outlook') document.getElementById('outlook').style.display = 'none';
        if (layerID!='vcard') document.getElementById('vcard').style.display = 'none';
        if (layerID!='vcardpd') document.getElementById('vcardpd').style.display = 'none';
        if (layerID!='csv') document.getElementById('csv').style.display = 'none';
        if (layerID!='ldif') document.getElementById('ldif').style.display = 'none';
        modes = new Array;
        modes[0] = 'none';
        modes[1] = 'block';

        if(isNaN(mode))
          currentRef.display = (currentRef.display == 'none') ? 'block' : 'none';
        else
          currentRef.display = modes[mode];
      }
    }
  //-->
  </SCRIPT>
  <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
  <TR>
    <TD align="right" width="37" nowrap class="sousMenu"><IMG src="image/trans.gif" alt="" width="35" height="1" border="0"></TD>
    <TD height="28" width="100%" class="sousMenu"><?php aff_alph($lettre); ?></TD>
    <TD align="right" width="37" nowrap class="sousMenu" style="text-align:right;"><A href="javascript: parent.imprime('<?php echo $tcMenu; ?>','<?php echo $sd; ?>','ALL');"><IMG src="image/impression.gif" title="<?php echo trad("CALEPIN_IMPRIMER");?>" width="23" height="21" border="0" align="absmiddle"></A>&nbsp;&nbsp;</TD>
  </TR>
  </TABLE>
  <BR>
  <FORM method="POST" name="FormRecherche" action="?sid=<?php echo $sid; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>">
  <TABLE cellspacing="0" cellpadding="0" width="465" border="0">
  <TR>
    <TD bgcolor="<?php echo $bgColor[1]; ?>" height="20" class="tabInput"><?php aff_rech(); ?></TD>
  </TR>
  <TR>
    <TD align="center" bgcolor="<?php echo $bgColor[0]; ?>" height="20" class="tabInput"><?php echo trad("CALEPIN_CHOIX_GROUPE");?>
    <SELECT name="zlGroupe" size="1" onchange="javascript: window.location.href='?sid=<?php echo $sid; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>&grp=' + this.value;">
      <OPTION value="0">(<?php echo trad("CALEPIN_CHOISIR_GROUPE");?>)</OPTION>
      <OPTION value="100000000"<?php if ($grp==100000000) echo " selected style=\"background:".$CalFond.";\""; ?>><?php echo trad("CALEPIN_LIB_PARTAGE");?></OPTION>
<?php aff_groupe(0,0,false,$grp); ?>
    </SELECT></TD>
  </TR>
  </TABLE>
  </FORM>
  <BR>
  <TABLE cellspacing="0" cellpadding="0" width="465" border="0">
<?php
  $idCA += 0; //Affichage des informations du contact associe a une note
  if ($msg_spe != "")
    echo  "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLRB\" align=\"center\">".$msg_spe."</TD>\n  </TR>\n";
  elseif ($lettre != "") {
    if ($msg_maj != "")
      echo  "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLRB\" align=\"center\">".$msg_maj."</TD>\n  </TR>\n";
    aff_res("nom",$lettre);
  }
  elseif ($idCA>0)
    aff_res("id",$idCA);
  elseif ($sur != "")
    aff_res($sur,trim($rech_txt));
  elseif ($grp)
    aff_res("groupe",$grp);
  elseif ($type == "Nouveau") {
    //Affichage du message de confirmation d'enregistrement d'un contact si clic sur Recommence
    if ($msg_maj != "")
      echo  "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLR\" align=\"center\">".$msg_maj."</TD>\n  </TR>\n";
    aff_nouveau($err);
  }
  elseif ($type == "Importer")
    aff_import();
  else
    echo "  <TR bgcolor=\"".$CalepinFondMessage."\">\n    <TD class=\"bordTLRB\" align=\"center\">".trad("CALEPIN_SELECT_CRITERE")."</TD>\n  </TR>\n";
?>
  </TABLE>
<?php
  if (!$id && $type != "Nouveau") {
    echo ("  <SCRIPT type=\"text/javascript\">
  <!--
    document.FormRecherche.rech_txt.focus();
  //-->
  </SCRIPT>\n");
  }
?>
<!-- FIN MODULE CALEPIN -->
