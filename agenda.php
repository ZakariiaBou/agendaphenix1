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
  require("inc/html.inc.php");
  include("inc/param.inc.php");
  if (isset($sid)) {
    include("inc/fonctions.inc.php");
    $start_time=get_moment();
  } else {
    Header("location: deconnexion.php?msg=5");
    exit;
  }

  $idUser = Session_ok($sid);

  include("skins/$APPLI_STYLE.php");
  include("lang/$APPLI_LANGUE.php");

  $sd += 0;
  $id += 0;
  $tcMenu += 0;
  $tcType += 0;
  $tcPlg  += 0;

  // Recuperation des infos de timezone de l'utilisateur
  $DB_CX->DbQuery("SELECT tzn_libelle, tzn_gmt, tzn_date_ete, tzn_heure_ete, tzn_date_hiver, tzn_heure_hiver, t1.util_format_heure FROM ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2, ${PREFIX_TABLE}timezone WHERE t1.util_id=".$USER_SUBSTITUE." AND t2.util_id=".$idUser." AND ((tzn_zone=t1.util_timezone AND t2.util_timezone_partage='O') OR (tzn_zone=t2.util_timezone AND t2.util_timezone_partage='N'))");
  $tzLibelle = htmlentities($DB_CX->DbResult(0,0));
  $tzGmt = $DB_CX->DbResult(0,1);
  $tzEte = calculBasculeDST($DB_CX->DbResult(0,2),gmdate("Y"),$DB_CX->DbResult(0,3),$tzGmt,0);
  $tzHiver = calculBasculeDST($DB_CX->DbResult(0,4),gmdate("Y"),$DB_CX->DbResult(0,5),$tzGmt,1);
  $formatHeure = $DB_CX->DbResult(0,6)==12 ? "h:ia" : "H:i";
  $LG['COMMUN_FORMAT_DATE_CREATION'] = $DB_CX->DbResult(0,6)==12 ? str_replace(array("%H","%M"), array("%I","%M%p"), $LG['COMMUN_FORMAT_DATE_CREATION']) : $LG['COMMUN_FORMAT_DATE_CREATION'];

  // Ajustement de la date en fonction du timezone
  $decalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(gmdate("H"),gmdate("i"),0,gmdate("n"),gmdate("j"),gmdate("Y")));
  $localTime = mktime(gmdate("H")+floor($decalageHoraire),gmdate("i")+($decalageHoraire*60)%60,gmdate("s"),gmdate("n"),gmdate("j"),gmdate("Y"));

  //Recuperation de la date a traiter
  if (!$sd) {
    if (!isset($jour))  $jour = ((!empty($mois)) ? 1 : date("j",$localTime));
    if (!isset($mois))  $mois = date("n",$localTime);
    if (!isset($annee)) $annee = date("Y",$localTime);
    $sd = mktime(12,0,0,$mois, $jour, $annee);
  }
  $jourEnCours  = date("d", $sd);
  $moisEnCours  = date("m", $sd);
  $anneeEnCours = date("Y", $sd);

  //Si choix d'ajout d'un nouveau contact par la liste deroulante
  if ($tcType==_TYPE_CONTACT) {
    $tcMenu = _MENU_CONTACT;
    $type = "Nouveau";
  }
  // Si choix d'import de contacts
  elseif ($tcType==_TYPE_IMPORT_CONTACT) {
    $tcMenu = _MENU_CONTACT;
    $type = "Importer";
  }
  elseif ($tcType && $tcMenu==_MENU_CONTACT) {
    $tcMenu = _MENU_PLG_QUOT;
  }

  switch ($tcMenu) {
    case _MENU_PLG_QUOT :
    case _MENU_PLG_HEBDO :
    case _MENU_PLG_MENSUEL :
    case _MENU_PLG_ANNUEL :
      if ($tcMenu == _MENU_PLG_HEBDO || $tcMenu == _MENU_PLG_MENSUEL) {
        // Affichage de la semaine type de l'utilisateur
        $nbJSelect=0;
        if (!isset($btFiltreAffiche)) {
          for ($i=1; $i<8; $i++) {
            ${"bt".$i} = substr($SEMAINE_TYPE,$i-1,1);
            if (${"bt".$i}==1)
              $nbJSelect++;
          }
        } else {
          $SEMAINE_TYPE="";
          for ($i=1; $i<8; $i++) {
            if (${"bt".$i}==1)
              $nbJSelect++;
            $SEMAINE_TYPE .= ${"bt".$i} + 0;
          }
          $FILTRE_COULEUR = $zlFiltreCouleur;
        }
        $nbJourMois = date("t", $sd);
      } elseif (($tcMenu == _MENU_PLG_QUOT || $tcMenu == _MENU_PLG_ANNUEL) && isset($btFiltreAffiche)) {
        $FILTRE_COULEUR = $zlFiltreCouleur;
      }
      // MAJ de la semaine type et du filtre des notes de l'utilisateur dans la table des sessions
      $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_semaine_type='".$SEMAINE_TYPE."', sid_filtre_couleur='".$FILTRE_COULEUR."' WHERE sid_id='".$sid."'");
      // Generation de variables pour recuperer les noms complet du createur et du modificateur d'une note
      $NOM_UTIL_CREATEUR = str_replace("util_","t1.util_",$FORMAT_NOM_UTIL);
      $NOM_UTIL_MODIFICATEUR = str_replace("util_","t2.util_",$FORMAT_NOM_UTIL);
      break;
    case _MENU_PLG_MENS_GBL :
      //Nombre de jours dans le mois
      $nbJourMois = date("t", $sd);
    case _MENU_PLG_HEBDO_GBL :
    case _MENU_PLG_QUOT_GBL :
      if (isset($btFiltreAffiche)) {
        $FILTRE_COULEUR = $zlFiltreCouleur;
      }
      $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_filtre_couleur='".$FILTRE_COULEUR."' WHERE sid_id='".$sid."'");
      break;
    case _MENU_DISP_HEBDO :
    case _MENU_DISP_QUOT :
    case _MENU_RECHERCHE :
    case _MENU_CONTACT :
    case _MENU_NOTE_IMPORT :
    case _MENU_NOTE_EXPORT :
    case _MENU_ADMIN : break;
    case _MENU_PROFIL : if ($droit_PROFILS == _DROIT_PROFIL_RIEN) $tcMenu = _MENU_PLG_QUOT; break;
    default : $tcMenu = _MENU_PLG_QUOT;
  }

  //Enregistrement du planning en cours de consultation
  if ($tcMenu<_MENU_DISP_HEBDO && !$tcType) {
    $tcPlg = $tcMenu;
  }

  // Info pour le calendrier permanent des semaines
  $indexJourCrt = date("w",$sd);
  if ($indexJourCrt == 0)
    $indexJourCrt = 7;
  $premierJourSemaine = $jourEnCours-$indexJourCrt+1;
  $debutSemaine = mktime(12,0,0,$moisEnCours,$premierJourSemaine,$anneeEnCours);
  $finSemaine   = mktime(12,0,0,$moisEnCours,$premierJourSemaine+6,$anneeEnCours);

  // On determine le TimeStamp du premier lundi de la semaine courante pour l'indiquer dans le calendrier
  $iJCrt = date("w",$localTime);
  if ($iJCrt == 0)
    $iJCrt = 7;
  $tsSemaineCrt = mktime(12,0,0,date("m",$localTime), date("j",$localTime)-$iJCrt+1, date("Y",$localTime));

  // On verifie la validite de la substitution par rapport a l'ecran demande
  if ($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL || $droit_AGENDAS < _DROIT_AGENDA_PARTAGE || ($tcMenu>=_MENU_DISP_HEBDO && $tcMenu!=_MENU_RECHERCHE && $USER_SUBSTITUE!=$idUser && $droit_AGENDAS < _DROIT_AGENDA_PARTAGE)) {
    $DB_CX->DbQuery("UPDATE ${PREFIX_TABLE}sid SET sid_util_subst_id=".$idUser." WHERE sid_id='".$sid."'");
    $USER_SUBSTITUE = $idUser;
  }

  // En cas de substitution, on verifie si on autorise l'ajout de notes
  // Depuis la version 3.0 cela est gere par la table PLANNING_AFFECTE
  if (($USER_SUBSTITUE!=$idUser) and ($droit_AGENDAS < _DROIT_AGENDA_TOUS)) {
    if ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR) {
      $DB_CX->DbQuery("SELECT util_id FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE (util_id=".$USER_SUBSTITUE." AND util_autorise_affect='1') OR (util_id=".$USER_SUBSTITUE." AND util_autorise_affect IN ('2','3') AND paf_consultant_id=".$idUser.")");
      $AFFECTE_NOTE = ($DB_CX->DbNumRows());
      if ($AFFECTE_NOTE == false)
        $droit_NOTES = _DROIT_NOTE_STANDARD;
    } elseif ($droit_NOTES >= _DROIT_NOTE_CONSULT_RECHERCHE) {
      $AFFECTE_NOTE = false;
      $droit_NOTES = _DROIT_NOTE_STANDARD;
    }
  } else {
    $AFFECTE_NOTE = true;
  }

  entete_page();
?>
  <SCRIPT language="JavaScript" type="text/javascript">
  <!--
// DEBUT - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui suit
    function affAbout(_statut) {
      document.getElementById("page_about").style.visibility = _statut;
    }
// FIN - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui precede

    // Changement d'utilisateur (partage de planning)
    function substUser(_suid) {
<?php
  switch ($tcMenu) {
    case _MENU_PLG_MENS_GBL : $tcMenu1=_MENU_PLG_MENSUEL; break;
    case _MENU_PLG_HEBDO_GBL : $tcMenu1=_MENU_PLG_HEBDO; break;
    case _MENU_PLG_QUOT_GBL : $tcMenu1=_MENU_PLG_QUOT; break;
    default : $tcMenu1=$tcMenu;
  }
?>
      window.location.href = "agenda_traitement.php?sid=<?php echo $sid; ?>&sd=<?php echo gmdate("Y-n-j", $sd); ?>&tcMenu=<?php echo $tcMenu1; ?>&tcPlg=<?php echo $tcPlg; ?>&ztAction=SUBST&suid="+_suid;
<?php
?>
    }

    // Changement de vue
    function nvlVu(_vu) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&sd=<?php echo $sd; ?>&tcMenu="+_vu+"&tcPlg=<?php echo $tcPlg; ?>";
    }

    // Affiche ou masque les favoris contenus dans un groupe
    function classToggle(element,class1,class2,img,chemin) {
      if (element && element.className==class1) {
        element.className = class2;
        img.src=chemin+"collapse1.gif";
      } else if (element && element.className==class2) {
        element.className = class1;
        img.src=chemin+"expand1.gif";
      }
    }

    // Fonction trim javascript (suppression d'espaces avant et apres une chaine)
    function trim(chaine) {
      return chaine.replace(/^\s+/, "").replace(/\s+$/, "");
    }
<?php
  if ($AFFECTE_NOTE) {
?>
    // Creation d'une note, d'un anniversaire, d'un contact ou d'un evenement
    function nvType() {
      var tArg = nvType.arguments;
      var _type = (tArg.length > 0) ? tArg[0] : "<?php echo _TYPE_NOTE; ?>";
      var _heure = (tArg.length == 2) ? "&hD="+tArg[1] : "";
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&sd=<?php echo $sd; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&tcType="+_type+_heure;
    }

    // Creation d'une note a partir du module disponibilite / equipe (avec les dates, heures et utilisateurs pre-selectionnes)
    function nvNote(_sd,_hD,_hF) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcType=<?php echo _TYPE_NOTE; ?>&sd="+_sd+"&hD="+_hD+"&hF="+_hF+"&sChoix=<?php echo $sChoix; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>";
    }
<?php
  }
  if (isset($lSup) && !empty($lSup)) {
?>
    // Fonction javascript qui ouvre une boite de dialogue indiquant que la note (lors d'une affectation)
    // qui vient d'etre cree ou modifiee se superpose avec d'autres notes pour les utilisateurs choisis
    function alerteNoteSuperpose(_sTmp) {
      alert("<?php echo trad("AGENDA_ALERTE_SUPERPOSITION");?>"+_sTmp);
    }

<?php
  }
?>
    // Recuperation des differentes options sur la selection des groupes
    function recupOptionsGrp(_vu) {
      var _str = "&tcMenu="+_vu;
      if (document.frmChoixGrp!=null) {
        var theForm = document.frmChoixGrp;
        // Liste des utilisateurs selectionnes
        recupSelection(theForm.zlConsulte, theForm.sChoix);
        if (trim(theForm.sChoix.value)!="") {
          _str += "&sChoix=" + theForm.sChoix.value;
        }
        // Precision d'affichage
        if (theForm.zlPrec!=null) {
          _str += "&zlPrec=" + theForm.zlPrec.value;
        }
        // Heure debut
        if (theForm.zlHD!=null) {
          _str += "&zlHD=" + theForm.zlHD.value;
        }
        // Heure fin
        if (theForm.zlHF!=null) {
          _str += "&zlHF=" + theForm.zlHF.value;
        }
        // Groupe
        _str += "&ggr=" + theForm.ggr.value;
        // Afficher non consultable / affectable
        _str += "&ckAffCache=" + ((theForm.ckAffCache.checked) ? "O" : "N");
        // Figer la vue
        _str += "&ckAffGr=" + ((theForm.ckAffGr.checked) ? "O" : "N");
        // Type d'affichage
        _str += "&ztActionGrp=" + theForm.ztActionGrp.value;
      }
      return _str;
    }

    // Reaffiche la page avec le calendrier choisi
    function affMois(_mois,_annee,_vu) {
      var _options = recupOptionsGrp(_vu);
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcPlg=<?php echo $tcPlg; ?>&mois="+_mois+"&annee="+_annee+_options;
    }

<?php
  // Script JS si le rechargement automatique des calendriers est actif
  if ($RELOAD_CALENDAR) {
    echo ("    // Recharge dynamiquement le calendrier journalier et des semaines avec la date choisie
    var _anneeCrt = '".intval($anneeEnCours)."';
    function rechargeCalendriers() {
      var _sid = '".$sid."';
      var _menu = '".$tcMenu."';
      var _jour = '".$jourEnCours."';
      var _mois = document.calForm['ztCalMois'].value;
      var _annee = document.calForm['ztCalAnnee'].value;
      var _sd = '".$sd."';
      var _majJour = (_mois=='".intval($moisEnCours)."' && _annee=='".intval($anneeEnCours)."') ? '2' : '1';
      var _majHebd = (_annee!=_anneeCrt) ? '1' : '0';
      _anneeCrt = _annee;
      parent.window.frames['trash_".$sid."'].window.location.href = \"agenda_calendrier_jours.php?sid=\"+_sid+\"&majJour=\"+_majJour+\"&majHebd=\"+_majHebd+\"&menu=\"+_menu+\"&jour=\"+_jour+\"&mois=\"+_mois+\"&annee=\"+_annee+\"&sd=\"+_sd;
    }\n\n");
  } else {
    // Sinon script JS pour action sur le bouton OK qui est affiche
    echo ("    // Reaffiche la page avec la date choisie
    function affCalSelect() {
      affMois(document.calForm['ztCalMois'].value,document.calForm['ztCalAnnee'].value,'".$tcMenu."');
    }\n\n");
  }
?>
    // Affiche les notes couvrant un jour choisi dans le calendrier
    function affJour(_sd) {
      var _options = recupOptionsGrp('<?php echo ($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) ? _MENU_PLG_QUOT_GBL : (($tcMenu==_MENU_DISP_HEBDO || $tcMenu==_MENU_DISP_QUOT) ? _MENU_DISP_QUOT : _MENU_PLG_QUOT); ?>');
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&sd="+_sd+_options;
    }

    // Affiche les notes couvrant une semaine choisie
    function affSemaine(_sd) {
      var _options = recupOptionsGrp('<?php echo ($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) ? _MENU_PLG_HEBDO_GBL : (($tcMenu==_MENU_DISP_HEBDO || $tcMenu==_MENU_DISP_QUOT) ? _MENU_DISP_HEBDO : _MENU_PLG_HEBDO); ?>');
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcPlg=<?php echo $tcPlg; ?>&sd="+_sd+_options;
    }

    // Ajuste la date dans le module calendrier
    function initTabMois() {
      return new Array("<?php echo trad("COMMUN_JANVIER");?>","<?php echo trad("COMMUN_FEVRIER");?>","<?php echo trad("COMMUN_MARS");?>","<?php echo trad("COMMUN_AVRIL");?>","<?php echo trad("COMMUN_MAI");?>","<?php echo trad("COMMUN_JUIN");?>","<?php echo trad("COMMUN_JUILLET");?>","<?php echo trad("COMMUN_AOUT");?>","<?php echo trad("COMMUN_SEPTEMBRE");?>","<?php echo trad("COMMUN_OCTOBRE");?>","<?php echo trad("COMMUN_NOVEMBRE");?>","<?php echo trad("COMMUN_DECEMBRE");?>");
    }
    function upDown(id, val) {
      var quantite = parseInt(val);
      if (id=="ztCalMois") {
        var tabMois = initTabMois()
        if (isNaN(quantite) || quantite < 0 || quantite > 11) { quantite = 0; }
        document.calForm[id].value = quantite+1;
        document.calForm['ztCalMois2'].value = tabMois[quantite];
      }
      else {
        if (isNaN(quantite) || quantite < 1970 || quantite > 2038) { quantite = 1970; }
        document.calForm[id].value = quantite;
      }
      cacheListe();
<?php
  if ($RELOAD_CALENDAR) {
    echo "      rechargeCalendriers();\n";
  }
?>
    }
    // Masque les listes deroulantes en DHTML
    function retardeLancement(_methode,_delai) {
      clearTimeout(window.div_timer);
      window.div_timer=setTimeout(_methode,_delai);
    }
    var ie6 = (navigator.userAgent.indexOf('MSIE 5')>0 || navigator.userAgent.indexOf('MSIE 6')>0) ? true : false;
    function cacheListe() {
      document.getElementById('sel_planning').style.display='none';
      if (document.getElementById('sel_ajouter') != null)
        document.getElementById('sel_ajouter').style.display='none';
      if (document.getElementById('sel_dispo') != null)
        document.getElementById('sel_dispo').style.display='none';
      if (document.getElementById('sel_outils') != null)
        document.getElementById('sel_outils').style.display='none';
      if (document.getElementById('sel_util') != null)
        document.getElementById('sel_util').style.display='none';
      if (document.getElementById('sel_mois') != null)
        document.getElementById('sel_mois').style.display='none';
      if (document.getElementById('sel_annee') != null)
        document.getElementById('sel_annee').style.display='none';
      // On reaffiche les listes deroulantes genantes pour ie < 7
      if (ie6) {
        if (document.getElementById('zlSubst') != null)
          document.getElementById('zlSubst').style.visibility='visible';
        if (document.getElementById('ggr1') != null)
          document.getElementById('ggr1').style.visibility='visible';
        if (document.getElementById('zlFiltreCouleur') != null)
          document.getElementById('zlFiltreCouleur').style.visibility='visible';
      }
    }
    // Affiche une liste deroulante en DHTML
    function showListe(_nomDiv,_nomChamp) {
      clearTimeout(window.div_timer);
      cacheListe();
      // On masque les listes deroulantes genantes pour ie < 7
      if (ie6) {
        if ((_nomDiv == 'sel_planning' && document.getElementById('sel_ajouter') == null) || _nomDiv=='sel_ajouter')
          if (document.getElementById('zlSubst') != null)
            document.getElementById('zlSubst').style.visibility='hidden';
        if (_nomDiv == 'sel_planning')
          if (document.getElementById('ggr1') != null)
            document.getElementById('ggr1').style.visibility='hidden';
        if (_nomDiv == 'sel_outils')
          if (document.getElementById('zlFiltreCouleur') != null)
            document.getElementById('zlFiltreCouleur').style.visibility='hidden';
      }
      if (_nomDiv=='sel_mois' || _nomDiv=='sel_annee') {
        updateListe(_nomDiv,_nomChamp);
      }
      document.getElementById(_nomDiv).style.display='block';
    }
    // Met a jour les listes deroulantes mois et annee en fonction de la selection courante
    function updateListe(_nomDiv,_nomChamp) {
      var val = document.calForm[_nomChamp].value;
      val = parseInt(val);
      var txt = "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"paddingDG3\">";
      var bordure = "";
      if (_nomDiv=="sel_mois") {
        var tabMois = initTabMois()
        for (var i=0;i<12;i++) {
          if (i+1==val) {
            bordure = "";
            if (i>0)
              bordure = "T";
            if (i!=11)
              bordure += "B";
            txt += "<TR><TD align=\"center\" class=\"bord"+ bordure +"\" bgcolor=\"<?php echo $ListeChoixSelection; ?>\" onclick=\"javascript:upDown('ztCalMois',"+ i +");\" style=\"cursor:pointer;\"><B>"+ tabMois[i] +"</B></TD></TR>";
          } else {
            txt += "<TR><TD align=\"center\" onmouseover=\"javascript:this.style.backgroundColor='<?php echo $ListeChoixSurvol; ?>';\" onmouseout=\"javascript:this.style.backgroundColor='';\" onclick=\"javascript:upDown('ztCalMois',"+ i +");\" style=\"cursor:pointer;\">"+ tabMois[i] +"</TD></TR>";
          }
        }
      } else {
        var borneInf = Math.max(val-5,1970);
        var borneSup = Math.min(val+7,2038);
        for (var i=borneInf;i<borneSup;i++) {
          if (i==val) {
            bordure = "";
            if (i>borneInf)
              bordure = "T";
            if (i!=(borneSup-1))
              bordure += "B";
            txt += "<TR><TD align=\"center\" class=\"bord"+ bordure +"\" bgcolor=\"<?php echo $ListeChoixSelection; ?>\" onclick=\"javascript:upDown('ztCalAnnee',"+ i +");\" style=\"cursor:pointer;\"><B>"+ i +"</B></TD></TR>";
          } else {
            txt += "<TR><TD align=\"center\" onmouseover=\"javascript:this.style.backgroundColor='<?php echo $ListeChoixSurvol; ?>';\" onmouseout=\"javascript:this.style.backgroundColor='';\" onclick=\"javascript:upDown('ztCalAnnee',"+ i +");\" style=\"cursor:pointer;\">"+ i +"</TD></TR>";
          }
        }
      }
      txt += "</TABLE>";
      layerWrite(txt,_nomDiv);
    }

<?php
  if (($tcMenu<_MENU_DISP_HEBDO || $tcMenu==_MENU_RECHERCHE) && !$tcType) {
?>
    // Affiche une note en modification
    function affNote(_note) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcType=<?php echo _TYPE_NOTE; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>&id="+_note;
    }

    // Affiche un anniv en modification
    function affAnniv(_anniv) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcType=<?php echo _TYPE_ANNIV; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>&id="+_anniv;
    }

    // Affiche un evenement en modification
    function affEvent(_event) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcType=<?php echo _TYPE_EVENEMENT; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>&id="+_event;
    }

    // Affiche un contact en modification
    function affContact(_contact) {
      window.location.href = "agenda.php?sid=<?php echo $sid; ?>&tcMenu=<?php echo _MENU_CONTACT; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo $sd; ?>&idCA="+_contact;
    }

    // Appropriation d'une note par un destinataire unique
    function apprNote(_note) {
      var _options = "<?php echo (($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) ? "&ggr=".$ggr."&ztActionGrp=".$ztActionGrp : ""); ?>";
      window.location.href = "agenda_traitement.php?sid=<?php echo $sid; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo gmdate("Y-n-j",$sd); ?>&ztFrom=note&ztAction=APPROPRIATION&idAge="+ _note+_options;
    }

<?php
  }
  if ($tcMenu<_MENU_DISP_HEBDO || $tcType==_TYPE_NOTE) {
?>
    // Suppression d'une occurence d'une note recurente
    function supprOcc(_note,_flag) {
      var msgAlert;
      var _options = "<?php echo (($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) ? "&ggr=".$ggr."&ztActionGrp=".$ztActionGrp : ""); ?>";
      if (_flag == '2') msgAlert = "<?php echo trad("AGENDA_ALERTE_SUP_NOTE_AFFECTEE");?>";
      else if (_flag == '1') msgAlert = "<?php echo trad("AGENDA_ALERTE_SUP_NOTE_RECURRENTE");?>";
      else msgAlert = "<?php echo trad("AGENDA_ALERTE_SUP_OCCURENCE");?>";
      if (confirm(msgAlert)) {
        window.location.href = "agenda_traitement.php?sid=<?php echo $sid; ?>&tcMenu=<?php echo $tcMenu; ?>&tcPlg=<?php echo $tcPlg; ?>&sd=<?php echo gmdate("Y-n-j",$sd); ?>&ztFrom=note&ztAction=DELETE&flag="+ _flag +"&idAge="+ _note+_options;
      }
    }
<?php
  }
  if ($tcType || $tcMenu>_MENU_RECHERCHE) {
?>

    // Action du bouton Annuler dans les saisies
    function btAnnul() {
      var _options = "<?php echo (($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) ? "&ggr=".$ggr."&ztActionGrp=".$ztActionGrp : ""); ?>";
      window.location.href = "<?php echo ("agenda.php?sid=".$sid.(($tcType && $tcType>_TYPE_IMPORT_CONTACT) ? "&tcType=".$tcType : "")."&tcMenu=".(($tcMenu!=_MENU_PROFIL) ? $tcMenu : $tcPlg)."&tcPlg=$tcPlg&sd=$sd"); ?>"+_options;
    }
<?php
  }
  if ($tcType==_TYPE_NOTE || ($tcMenu<_MENU_DISP_HEBDO) || ($tcMenu==_MENU_RECHERCHE) || ($tcType==_TYPE_LIBELLE)) {
?>

    // Change la couleur de fond de la premiere selection d'une liste deroulante
    // et assigne eventuelement du champ "_champ"
    function changeCouleurListe(_liste,_champ) {
      _liste.style.backgroundColor=_liste.options[_liste.selectedIndex].style.backgroundColor;
      if (_champ) {
        _champ.style.backgroundColor=_liste.style.backgroundColor;
      }
    }
<?php
  }
  if ($tcType==_TYPE_EVENEMENT || $tcType==_TYPE_FAVORIS) {
?>

    // Affiche une liste en fonction du groupe choisi et ferme celle precedemment ouverte
    function affListe(_liste,_element1,_element2,_nvListe,chemin) {
      var oldListe = _liste.value;
      if (oldListe!=_nvListe) {
        classToggle2(document.getElementById(_element1+oldListe),'displayNone','displayBlock',document.getElementById(_element2+oldListe),chemin);
        _liste.value = _nvListe;
      } else {
        _liste.value = "0";
      }
      classToggle2(document.getElementById(_element1+_nvListe),'displayNone','displayBlock',document.getElementById(_element2+_nvListe),chemin);
    }

    // Affiche ou masque le contenu d'une liste
    function classToggle2(element,class1,class2,img,chemin) {
      if (element && element.className==class1) {
        element.className = class2;
        img.src=chemin+"collapse_fav.gif";
      } else if (element && element.className==class2) {
        element.className = class1;
        img.src=chemin+"expand_fav.gif";
      }
    }
<?php
  }
?>
  //-->
  </SCRIPT>
<?php
  if ($tcType==_TYPE_ANNIV || $tcType==_TYPE_NOTE || $tcType==_TYPE_CONTACT || $tcType==_TYPE_EVENEMENT || $tcMenu==_MENU_RECHERCHE || ($tcMenu==_MENU_CONTACT && $ztAction=="M")) {
    echo ("  <STYLE type=\"text/css\">@import url(css/calendar_css.php?id=".$APPLI_STYLE.");</STYLE>
  <SCRIPT type=\"text/javascript\" src=\"inc/calendar.js\"></SCRIPT>\n");
    include("inc/calendar-setup.js.php");
  }
  if ($tcType==_TYPE_NOTE) {
    // Liste des utilisateurs a qui l'on peut affecter une note
    $DB = new Db($DB_CX->ConnexionID);
    if ($droit_AGENDAS < _DROIT_AGENDA_PARTAGE) {
      $DB->DbQuery("SELECT DISTINCT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE util_id=".$idUser);
    } elseif($droit_AGENDAS >= _DROIT_AGENDA_TOUS) {
      if ($droit_NOTES < _DROIT_NOTE_MODIF_CREATION)
        $DB->DbQuery("SELECT DISTINCT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE util_id=".$idUser." OR (util_autorise_affect ='1') OR (util_autorise_affect IN ('2','3') AND paf_consultant_id=".$idUser.") ORDER BY nomUtil");
      else
        $DB->DbQuery("SELECT DISTINCT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id LEFT JOIN ${PREFIX_TABLE}planning_partage ON ppl_util_id=util_id WHERE (LENGTH(CONCAT(util_nom, util_prenom)) > 0) ORDER BY nomUtil");
    } else {
      $DB->DbQuery("SELECT DISTINCT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE util_id=".$idUser." OR (util_autorise_affect ='1') OR (util_autorise_affect IN ('2','3') AND paf_consultant_id=".$idUser.") ORDER BY nomUtil");
    }
    $nbAffect = $DB->DbNumRows();
    $onLoad = "ajustHeureDuree();";
    if ($nbAffect>1)
      $onLoad .= " selectUtil(document.Form1.zlUtilisateur, document.Form1.zlParticipant);";
  } elseif (!$tcType && ($tcMenu==_MENU_PLG_MENS_GBL || $tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL || $tcMenu==_MENU_DISP_HEBDO || $tcMenu==_MENU_DISP_QUOT || ($tcMenu==_MENU_ADMIN && $groupe==1))) {
    $onLoad = "selectUtil(document.frmChoixGrp.zlUtilisateur, document.frmChoixGrp.zlConsulte); ";
  } elseif (!$tcType && $tcMenu==_MENU_PROFIL) {
    $onLoad = "selectUtil(document.frmProfil.zlUtilisateur, document.frmProfil.zlPartage); selectUtil(document.frmProfil.zlUtilisateur2, document.frmProfil.zlAffecte); ";
    $onLoad .= "InitProfil();";
  }
  // On regarde le parametre renvoye par agenda_traitement lors de la creation ou modification d'une note (lors de l'affectation)
  // Permet de renseigner l'utilisateur qui a affecte la note d'une superposition et de corriger si besoin
  if (isset($lSup) && !empty($lSup)) {
    $sTmp = "";
    $DB_CX->DbQuery("SELECT CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil FROM ${PREFIX_TABLE}utilisateur WHERE util_id IN (".$lSup.") ORDER BY nomUtil");
    while ($enr = $DB_CX->DbNextRow()) {
      $sTmp .= "\\t- ".addslashes($enr['nomUtil'])."\\n";
    }
    $sAlert = "alerteNoteSuperpose('".$sTmp."');";
  } else {
    $sAlert = "";
  }

  // Recuperation des evenements personnalises a notifier dans le calendrier (sert aussi pour le planning mensuel global)
  $DB_CX->DbQuery("SELECT DISTINCT eve_date_debut, TO_DAYS(eve_date_fin)-TO_DAYS(eve_date_debut) AS duree, TO_DAYS(eve_date_debut)-TO_DAYS('$anneeEnCours-$moisEnCours-01') AS decalage, eve_couleur FROM ${PREFIX_TABLE}evenement WHERE (eve_date_debut LIKE '$anneeEnCours-$moisEnCours-%' OR (eve_date_debut<'$anneeEnCours-$moisEnCours-01' AND eve_date_fin>='$anneeEnCours-$moisEnCours-01'))".(($USER_SUBSTITUE==$idUser) ? " AND (eve_util_id=".$idUser." OR eve_partage='O')" : " AND eve_partage='O'"));
  $tabEvenementDate = array();
  // Initialisation du tableau des couleurs des jours a vide
  $nbJourMois = date("t",$sd);
  for ($i=1;$i<$nbJourMois;$i++) {
    $tabEvenementDate[$i] = "";
  }
  while ($enr = $DB_CX->DbNextRow()) {
    $dureeEvt = $enr['duree'];
    list($aEvt,$mEvt,$jEvt) = explode ("-",$enr['eve_date_debut']);
    if ($enr['decalage']<0) { // La date de debut est anterieure au mois courant donc il faut regulariser
      $jEvt=1;
      $dureeEvt = $dureeEvt+$enr['decalage']; // On additionne car $enr['decalage'] est negatif
    }
    if ($dureeEvt > ($nbJourMois-$jEvt)) { // La date de fin est posterieure au mois courant, donc il faut regulariser
      $dureeEvt = $nbJourMois-$jEvt;
    }
    if (empty($enr['eve_couleur']))
      $enr['eve_couleur'] = $CalJourEvenement;
    for ($i=0;$i<=$dureeEvt;$i++) {
      $tabEvenementDate[intval($jEvt+$i)] = $enr['eve_couleur'];
    }
  }
?>
</HEAD>

<BODY onLoad="javascript: <?php echo $onLoad."show_clock();".((($tcMenu==_MENU_PLG_HEBDO_GBL || $tcMenu==_MENU_PLG_QUOT_GBL) && !$tcType) ? "ChangeScreen();" : "").$sAlert; ?>" leftmargin="0" topmargin="0" rightmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
  <DIV id="infoBulle" style="position:absolute; visibility:hidden; z-index: 20;"></DIV>
  <SCRIPT language="JavaScript" src="inc/infobulle.js"></SCRIPT>
  <TABLE width="100%" border="0" cellspacing="0" cellpadding="0">
  <TR>
    <TD width="134" height="66" colspan="2" nowrap><TABLE width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:separate;">
      <TR>
<!-- DEBUT - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui suit -->
        <TD height="46" align="center" valign="middle" nowrap bgcolor="<?php echo $MenuTitreFond; ?>" onclick="javascript: affAbout('visible');" style="cursor:pointer;"><A class="MenuLienAppli" title="<?php echo trad("AGENDA_TITLE_COPYRIGHT");?>"><B><?php echo sprintf(trad("AGENDA_VERSION_PHENIX"), $APPLI_VERSION); ?></B></A></TD>
<!-- FIN - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui precede -->
      </TR>
      <TR>
        <TD height="21" align="center" bgcolor="<?php echo $AujourdhuiFond; ?>" style="<?php echo $AujourdhuiStyle; ?>" nowrap<?php echo infoPopup("<CENTER>".date("d",$localTime)." ".strtolower($tabMois[date("n",$localTime)])." ".date("Y",$localTime)."<BR>".sprintf(trad("AGENDA_SEMAINE"), date("W",$tsSemaineCrt))."</CENTER>"); ?>><A class="Aujourdhui" href="agenda.php?sid=<?php echo $sid; ?>&tcMenu=0"><B><?php echo trad("AGENDA_AUJOURDHUI");?></B>&nbsp;<IMG src="image/calendrier/<?php echo date("j",$localTime); ?>.gif" width="16" height="16" align="absMiddle" border="0"></A></TD>
      </TR>
    </TABLE></TD>
    <TD width="100%" height="66" nowrap><TABLE width="100%" border="0" cellspacing="0" cellpadding="0">
      <TR>
        <TD height="22" valign="middle" nowrap><?php include("agenda_menu.php"); ?></TD>
      </TR>
      <TR>
        <TD height="44" nowrap><?php include("agenda_titre.php"); ?></TD>
      </TR>
    </TABLE></TD>
  </TR>
  <TR valign="top">
    <TD width="133" height="560" align="center" bgcolor="<?php echo $CalGaucheFondBas;?>" nowrap><?php include("agenda_calendrier.php"); ?></TD>
    <TD width="1" nowrap bgcolor="<?php echo $CalAgendaSeparation; ?>"><IMG src="image/trans.gif" width="1"></TD>
    <TD align="center" nowrap>
<?php
  switch ($tcType) {
    case _TYPE_ANNIV : include("agenda_anniv.php"); break;
    case _TYPE_NOTE : include("agenda_note.php"); break;
    case _TYPE_EVENEMENT : include("agenda_evenement.php"); break;
    case _TYPE_MEMO : include("agenda_memo.php"); break;
    case _TYPE_LIBELLE : include("agenda_libelle.php"); break;
    case _TYPE_FAVORIS : include("agenda_favoris.php"); break;
    default :
      switch ($tcMenu) {
        case _MENU_PLG_HEBDO : include("agenda_hebdomadaire.php"); break;
        case _MENU_PLG_MENSUEL : include("agenda_mensuel.php"); break;
        case _MENU_PLG_ANNUEL : include("agenda_annuel.php"); break;
        case _MENU_PLG_MENS_GBL : include("agenda_mensuel_global.php"); break;
        case _MENU_PLG_HEBDO_GBL : include("agenda_hebdomadaire_global.php"); break;
        case _MENU_PLG_QUOT_GBL : include("agenda_quotidien_global.php"); break;
        case _MENU_DISP_HEBDO : include("agenda_dispo_hebdo.php"); break;
        case _MENU_DISP_QUOT : include("agenda_dispo_jour.php"); break;
        case _MENU_RECHERCHE : include("agenda_recherche.php"); break;
        case _MENU_CONTACT : include("agenda_calepin.php"); break;
        case _MENU_PROFIL : include("agenda_profil.php"); break;
        case _MENU_NOTE_IMPORT : include("agenda_note_import.php"); break;
        case _MENU_NOTE_EXPORT : include("agenda_note_export.php"); break;
        case _MENU_ADMIN : include("admin/admin.php"); break;
        default : include("agenda_quotidien.php"); break;
      }
  }

  if ($tcMenu<_MENU_PLG_ANNUEL && !$tcType) {
?>
      <BR>
      <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
      <TR align="center">
        <TD height="20" colspan="2" class="legende">&nbsp;<IMG src="image/rappel.gif" alt="" border="0" align="absmiddle">&nbsp;<?php echo trad("AGENDA_LEGENDE_NOTE_RAPPEL");?>&nbsp;&nbsp;&nbsp;&nbsp;<IMG src="image/recurrent.gif" width="13" height="11" alt="" border="0" align="absmiddle">&nbsp;<?php echo trad("AGENDA_LEGENDE_SUP_OCCURENCE");?>&nbsp;&nbsp;&nbsp;&nbsp;<IMG src="image/suppr.gif" alt="" width="12" height="12" border="0" align="absmiddle">&nbsp;<?php echo trad("AGENDA_LEGENDE_SUP_NOTE");?>&nbsp;&nbsp;&nbsp;&nbsp;<IMG src="image/appropriation.gif" alt="" width="13" height="11" border="0" align="absmiddle">&nbsp;<?php echo trad("AGENDA_LEGENDE_APPROPRIER_NOTE");?>&nbsp;&nbsp;&nbsp;&nbsp;<IMG src="image/contact.gif" alt="" width="10" height="11" border="0" align="absmiddle">&nbsp;<?php echo trad("AGENDA_LEGENDE_CONTACT_ASSOCIE");?><?php if (!$tcMenu) { ?>&nbsp;&nbsp;&nbsp;&nbsp;<IMG src="image/popup_open.gif" width="9" height="8" alt="" border="0" align="top">&nbsp;<?php echo trad("AGENDA_LEGENDE_AFFICHER_DETAIL");} ?>&nbsp;</TD>
      </TR>
      <TR align="center" height="20">
        <TD height="28" align="center"><BR><TABLE cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
<?php
    if (!$tcMenu) {
      echo ("          <TR height=\"15\">
            <TD class=\"borderNotePerso\" bgcolor=\"".$AgendaFondNotePerso."\" align=\"center\" width=\"95\" nowrap>".trad("AGENDA_LEGENDE_NOTE_PERSO")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"borderNote\" bgcolor=\"".$AgendaFondNote."\" align=\"center\" width=\"95\" nowrap>".trad("AGENDA_LEGENDE_NOTE_AFFECTEE")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"legendeBis\"><IMG src=\"image/puce_ko.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_ACTIVE")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"legendeBis\"><IMG src=\"image/puce_ok.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_TERMINEE")."</TD>
          </TR>\n");
    } else {
      echo ("          <TR height=\"15\">
            <TD class=\"bordLegende\" bgcolor=\"".$CalJourSelection."\" align=\"center\" width=\"95\" nowrap>".trad("AGENDA_LEGENDE_JOUR_COURANT")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"bordLegende\" bgcolor=\"".$CalJourFerie."\" align=\"center\" width=\"95\" nowrap>".trad("AGENDA_LEGENDE_JOUR_FERIE")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"bordLegende\" bgcolor=\"".$CalJourEvenement."\" align=\"center\" width=\"95\" nowrap>".trad("COMMUN_EVENEMENT")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"legendeBis\"><IMG src=\"image/ajout_note.gif\" width=\"13\" height=\"15\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_CREER_NOTE")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"legendeBis\"><IMG src=\"image/puce_ko.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_ACTIVE")."</TD>
            <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
            <TD class=\"legendeBis\"><IMG src=\"image/puce_ok.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_TERMINEE")."</TD>
          </TR>\n");
    }
    if ($USER_SUBSTITUE==$idUser) {
      if (!$tcMenu) {
        echo ("          <TR>
            <TD colspan=\"4\">&nbsp;</TD>
            <TD colspan=\"3\" align=\"center\" class=\"legendeBis\">".trad("AGENDA_CLIC_CHANGER")."</TD>
          </TR>\n");
      } else {
        echo ("          <TR>
            <TD colspan=\"8\">&nbsp;</TD>
            <TD colspan=\"3\" align=\"center\" class=\"legendeBis\">".trad("AGENDA_CLIC_CHANGER")."</TD>
          </TR>\n");
      }
      echo ("          <TR>
            <TD height=\"4\"><IMG src=\"image/trans.gif\" width=\"1\" height=\"4\" alt=\"\" border=\"0\"></TD>
          </TR>\n");
    }
?>
        </TABLE></TD>
      </TR>
      </TABLE>
<?php
  }
  if ($AFF_INFO_DEBUG) {
    display_variables();
    echo "      <BR><CENTER><FONT class=\"generation\">&nbsp;".sprintf(trad("COMMUN_TEMPS_EXECUTION"),get_elapsed_time( $start_time, get_moment()))."&nbsp;<BR>&nbsp;".sprintf(trad("COMMUN_NB_REQUETE"),$DB_CX->DbNbReq())."&nbsp;</FONT></CENTER>\n";
  }
?>
    &nbsp;</TD>
  </TR>
  </TABLE>
<!-- DEBUT - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui suit -->
  <DIV id="page_about" style="position:absolute; top:5px; left:5px; visibility:hidden;z-index:150;">
    <TABLE border="0" cellpadding="0" cellspacing="1" width="510" class="infoBulle">
      <TR>
        <TD><TABLE width="100%" border="0" cellpadding="0" cellspacing="0"><TR><TD class="ibTitre" width="100%" align="center" style="FONT-SIZE: 14px;"><B>&nbsp;<?php echo trad("ABOUT_TITRE").sprintf(trad("AGENDA_VERSION_PHENIX"), $APPLI_VERSION); ?>&nbsp;</B></TD><TD align="right" class="ibTitre"><A href="#" onClick="affAbout('hidden');" title="<?php echo trad("POPUP_FERMER");?>"><IMG src="image/popup_close.gif" width="13" height="13" alt="<?php echo trad("POPUP_FERMER");?>" border="0"></A></TD></TR></TABLE></TD>
      </TR>
      <TR>
        <TD align="center" bgcolor="<?php echo $AgendaFondPopup; ?>" style="padding:5px"><?php include("inc/about.php"); ?></TD>
      </TR>
    </TABLE>
<!-- FIN - En accord avec la licence GPL sous laquelle Phenix est distribue, merci de ne pas supprimer ou modifier le code qui precede -->
  </DIV>
  <SCRIPT type="application/javascript; version=1.5">
  <!--
    if (document.implementation &&
      document.implementation.hasFeature('HTML', '2.0') &&
      document.implementation.hasFeature('StyleSheets', '2.0') &&
      document.implementation.hasFeature('CSS', '2.0')
    ) {
      var element = document.getElementsByTagName('head')[0].appendChild(document.createElement ('style'));
      element.type = 'text/css';
      element.sheet.insertRule('table { border-collapse: separate; }', 0);
      element.parentNode.removeChild(element);
    }
  //-->
  </SCRIPT>
<?php
  // Fermeture BDD
  $DB_CX->DbDeconnect();
?></BODY>
</HTML>
