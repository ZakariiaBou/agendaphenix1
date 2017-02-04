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
?>

<!-- MODULE PLANNING HEBDOMADAIRE GLOBAL -->
<?php
  // Constitution du titre de la page
  $lienAvant = mktime(12,0,0,$moisEnCours,$premierJourSemaine-7,$anneeEnCours);
  $lienApres = mktime(12,0,0,$moisEnCours,$premierJourSemaine+7,$anneeEnCours);
  $titrePage = "<B><A href=\"javascript: affSemaine('".$lienAvant."');\" class=\"sousMenu\"".infoPopup(trad('PLGL_SEMAINE_PRECEDENTE')."<B>".date("W",$lienAvant)."</B>").">&laquo;</A>&nbsp;&nbsp;".sprintf(trad("PLGL_SEMAINE_COURANTE"), date("W",$debutSemaine),date("d",$debutSemaine)." ".strtolower($tabMois[date("n",$debutSemaine)])." ".date("Y",$debutSemaine), date("d",$finSemaine)." ".strtolower($tabMois[date("n",$finSemaine)])." ".date("Y",$finSemaine))."&nbsp;&nbsp;<A href=\"javascript: affSemaine('".$lienApres."');\" class=\"sousMenu\"".infoPopup(trad('PLGL_SEMAINE_SUIVANTE')."<B>".date("W",$lienApres)."</B>").">&raquo;</A></B>";

  include("agenda_planning_groupe.php");

  // Si la liste contenant les identifiants des utilisateurs selectionnes n'est pas vide
  if (!empty($sChoix)) {
    function colSpanH($hDeb,$flag) {
      global $iDureeJournee;
      for ($i=$hDeb;$i<=$iDureeJournee;$i++) {
        return $i;
      }
      return $i;
    }

    $iHeureMin = "24";
    $iHeureMax = "0";
    $SemaineTypeTotal="0000000";
    //Preparation au decalage horaire
    $decalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$moisEnCours,$premierJourSemaine,$anneeEnCours));
    $bDecalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,mktime(12,0,0,$moisEnCours,$premierJourSemaine+6,$anneeEnCours));
    // Tableau contenant les id => nom des utilisateurs
    $aUtilPartage = array();
    // Tableau des heures de debut de journee pour chaque utilisateur
    $aHeureDebutJourneeUtil = array();
    $aHeureFinJourneeUtil = array();
    // Tableau contenant les decalages horaires
    $aUtilDoubleJours = array();
    $aUtiltzGmt = array();
    $aUtiltzEte = array();
    $aUtiltzHiver = array();
    $aUtilDecalageHoraire = array();
    $DB_CX->DbQuery("SELECT util_id, CONCAT(".$FORMAT_NOM_UTIL.") AS nomUtil, util_debut_journee, util_fin_journee, util_semaine_type, tzn_gmt, tzn_date_ete, tzn_heure_ete, tzn_date_hiver, tzn_heure_hiver, tzn_libelle, util_format_heure, util_timezone_partage FROM ${PREFIX_TABLE}utilisateur, ${PREFIX_TABLE}timezone WHERE util_id IN (".$sChoix.") AND (tzn_zone=util_timezone) ORDER BY nomUtil");
    while ($enr=$DB_CX->DbNextRow()) {
      // Recuperation des infos de timezone de l'utilisateur
      if ($enr['util_id']==$idUser)
        $AffTZ=$enr['util_timezone_partage'];
      $aUtiltzGmt[$enr['util_id']] = $enr['tzn_gmt'];
      $aUtiltzLibelle[$enr['util_id']] = $enr['tzn_libelle'];
      $aUtiltzEte[$enr['util_id']] = calculBasculeDST($enr['tzn_date_ete'],gmdate("Y"),$enr['tzn_heure_ete'],$aUtiltzGmt[$enr['util_id']],0);
      $aUtiltzHiver[$enr['util_id']] = calculBasculeDST($enr['tzn_date_hiver'],gmdate("Y"),$enr['tzn_heure_hiver'],$aUtiltzGmt[$enr['util_id']],1);
      $aUtilDecalageHoraire[$enr['util_id']] = calculDecalageH($aUtiltzGmt[$enr['util_id']],$aUtiltzEte[$enr['util_id']],$aUtiltzHiver[$enr['util_id']],mktime(12,0,0,$moisEnCours,$premierJourSemaine,$anneeEnCours));
      // Recuperation de l'heure de debut de journee minimale et de l'heure de fin de journee maximale selectionnees par l'utilisateur
      $aHeureDebutJourneeUtil[$enr['util_id']] = ($enr['util_debut_journee']-$aUtilDecalageHoraire[$enr['util_id']]+$decalageHoraire);
      $aHeureFinJourneeUtil[$enr['util_id']] = ($enr['util_fin_journee']-$aUtilDecalageHoraire[$enr['util_id']]+$decalageHoraire);
      if ($aHeureDebutJourneeUtil[$enr['util_id']]<0)
        $aHeureDebutJourneeUtil[$enr['util_id']] = $aHeureDebutJourneeUtil[$enr['util_id']]+24;
      if ($aHeureFinJourneeUtil[$enr['util_id']]<0)
        $aHeureFinJourneeUtil[$enr['util_id']] = $aHeureFinJourneeUtil[$enr['util_id']]+24;
      $iHeureMin = floor(min($aHeureDebutJourneeUtil[$enr['util_id']],$iHeureMin));
      $iHeureMax = ceil(max($aHeureFinJourneeUtil[$enr['util_id']],$iHeureMax));
      $SemaineType = bindec($enr['util_semaine_type']);
      $SemaineType |= bindec($SemaineTypeTotal);
      $SemaineTypeTotal = decbin($SemaineType);
      // Info sur les utilisateurs selectionnes
      $aUtilPartage[$enr['util_id']] = $enr['nomUtil'];
      $aUtilDoubleJours[$enr['util_id']]=false;
      if ((($aHeureDebutJourneeUtil[$enr['util_id']]) > ($aHeureFinJourneeUtil[$enr['util_id']]))) {
        $aHeureDebutJourneeUtil[$enr['util_id']] = ($enr['util_fin_journee']-$aUtilDecalageHoraire[$enr['util_id']]+$decalageHoraire);
        $aHeureFinJourneeUtil[$enr['util_id']] = ($enr['util_debut_journee']-$aUtilDecalageHoraire[$enr['util_id']]+$decalageHoraire);
        if ($aHeureDebutJourneeUtil[$enr['util_id']]<0)
          $aHeureDebutJourneeUtil[$enr['util_id']] = $aHeureDebutJourneeUtil[$enr['util_id']]+24;
        if ($aHeureFinJourneeUtil[$enr['util_id']]<0)
          $aHeureFinJourneeUtil[$enr['util_id']] = $aHeureFinJourneeUtil[$enr['util_id']]+24;
        $iHeureMin=0;
        $iHeureMax=24;
        $aUtilDoubleJours[$enr['util_id']]=true;
      }
      $vSemaineType[$enr['util_id']] = $enr['util_semaine_type']; // Semaine type mappee au format PHP (L->D => D->S)
    }
    if ($zlHD!="")
      $iHeureMin = floor($zlHD);
    else
      $zlHD = floor($iHeureMin);
    if ($zlHF!="")
      $iHeureMax = ceil($zlHF);
    else
      $zlHF = ceil($iHeureMax);
?>
  <BR><TABLE border="0" cellspacing="0" cellpadding="0" width="99%">
<?php
    //On mappe la semaine type (transformee dans agenda_calendrier)  au format PHP (Dim->Sam)
    $SEMAINE_CALENDRIER = substr($SEMAINE_CALENDRIER,6).substr($SEMAINE_CALENDRIER,0,6);
    //Entete du tableau
    // Definition de la date courante
    // Calcul de la duree de la journee pour le nb de colonnes
    $iDureeJournee = ceil($iHeureMax)-floor($iHeureMin);
    //Tableau des disponibilites 0->libre 1->Occupe 2->Hors Profil
    $aJournee = array();
    $sOutput = "";
    $sOutput1 = "";
    // Creation de la premiere ligne du tableau avec les plages horaires
    for ($i=0;$i<7;$i++) {
      if (substr($SemaineTypeTotal,$i,1)=="1") {
        $leJour = mktime(12,0,0,$moisEnCours,$premierJourSemaine+$i,$anneeEnCours);
        $numJour= date("j",$leJour);
        //Coloration des jours feries
        $ferie = date("j-m",$leJour);
        //Coloration des jours feries
        $DB_CX->DbQuery("SELECT DISTINCT eve_id, eve_libelle, eve_util_id, eve_type, DATE_FORMAT(eve_date_debut,'%d/%m/%Y') AS dateDebut, DATE_FORMAT(eve_date_fin,'%d/%m/%Y') AS dateFin FROM ${PREFIX_TABLE}evenement WHERE DATE_FORMAT(eve_date_debut,'%Y%m%d')<='".date("Ymd",$leJour)."' AND DATE_FORMAT(eve_date_fin,'%Y%m%d')>='".date("Ymd",$leJour)."'".(($USER_SUBSTITUE==$idUser) ? " AND (eve_util_id=".$idUser." OR eve_partage='O')" : " AND eve_partage='O'"));
        $foundEvent = ($DB_CX->DbNumRows()>0);

        if (in_array($ferie,$tabJourFerie)) {
          $classCel2 = "mensFerie";
        } elseif ($foundEvent==true) {
          $classCel2 = "mensEvenement";
        } else {
          $classCel2 = "jourPlanning";
        }
        if (date("Y-m-j",$leJour) == date("Y-m-j",$localTime))
          $classCel2 = "mensJour";

        if (substr($SEMAINE_CALENDRIER,date("w",$leJour),1)=="0") {
          $could = $CalJourWE;
        } else {
          $could = $AgendaTexte;
        }
        $affJourSem[$i]="class=\"".$classCel2."\"><A href=\"javascript: affJour('".$leJour."');\" title=\"".trad("PLGL_AFF_JOUR")."\"><B><FONT color=\"".$could."\">".$tabJour3[date("w",$leJour)]." ".date("d/m",$leJour)."</FONT>";
      }
    }

    for ($j=0;$j<7;$j++) {
      $aJournee[1][$j][6]=0;
      for ($i=0;$i<=$iDureeJournee;$i++) {
        //Initialisation du tableau de la journee a 0 (libre) et sans couleur par defaut
        $aJournee[$i][$j][0]="0";
        $aJournee[$i][$j][1]="";
        $aJournee[$i][$j][2]="&nbsp;";
        $aJournee[$i][$j][3]="";
        $aJournee[$i][$j][4]=false;
        $aJournee[$i][$j][5]=1;
      }
    }
    $PlNote=false;
    $PfNote=false;
    $NblignUtil=0;
    // Parcours du tableau d'utilisateur pour recuperer toutes les notes
    $NbJour=substr_count($SemaineTypeTotal,"1");
    $tailleCell = ($hdScreen>=1000 ? ($hdScreen-400): 600 )/($NbJour*$iDureeJournee);
    while (list($sUtilID,$sNomUtil)=each($aUtilPartage)) {
      // Affichage date
      if (($NblignUtil % 10)==0) {
        if ($NblignUtil==0)
          $sOutput .= "<TR>\n    <TD>&nbsp;</TD>\n";
        else
          $sOutput .= "<TR>\n    <TD style=\"background-color:".$PlanningJour."; border-left:solid 1px ".$AgendaBordureTableau.";\">&nbsp;</TD>\n";
        $sOutput1="";
        for ($i=0;$i<7;$i++) {
          if (substr($SemaineTypeTotal,$i,1)=="1") {
            $NbCell=0;
            for ($j=0;$j<$iDureeJournee;$j++) {
              $NbCell++;
              $heure = strval(floor($iHeureMin+$j));
              if (($formatHeure == "h:ia" and $heure > 12))
                $heure=$heure-12;
              if (strlen($heure)==2 and $tailleCell<16)
                $heure=$heure{0}."<br>".$heure{1};
              $sOutput1 .="    <TD align=\"center\" class=\"jourPlanning\" width=\"".$tailleCell."\" nowrap>".$heure."</TD>\n";
            }
            $sOutput .="    <TD align=\"center\" colspan=\"".$NbCell."\"height=\"18\" ".$affJourSem[$i]."</B></A></TD>\n";
          }
        }
        if ($NblignUtil==0)
          $sOutput .= "</TR>\n          <TR align=\"center\">          <TD>&nbsp;</TD>";
        else
          $sOutput .= "</TR>\n          <TR align=\"center\">          <TD style=\"background-color:".$PlanningJour."; border-left:solid 1px ".$AgendaBordureTableau.";\">&nbsp;</TD>";
        $sOutput.=$sOutput1;
        $sOutput .= " </TR>\n";
      }
      $NblignUtil++;

      // Pour chaque utilisateur on verifie si on a acces a son planning en modification
      $DB_CX->DbQuery("SELECT util_id FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_affecte ON paf_util_id=util_id WHERE util_id=".$sUtilID." AND (util_autorise_affect='1' OR (util_autorise_affect IN ('2','3') AND paf_consultant_id=".$idUser."))");
      if (($DB_CX->DbNumRows() && ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)) || ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION and $droit_AGENDAS >= _DROIT_AGENDA_TOUS) || $sUtilID==$idUser) {
        $autoriseAffect = true;
        $aAutoAffect[$sUtilID] = 1;
      } else {
        $autoriseAffect = false;
        $aAutoAffect[$sUtilID] = 0;
      }
      if ($AffTZ=="O")
        $styleUTC="<BR><SPAN style=\"font-weight:normal;font-style:normal;\"><SMALL>".sprintf(trad("PLGL_FUSEAU"), (($aUtiltzGmt[$sUtilID]<0) ? "-" : "+").afficheHeure(floor(abs($aUtiltzGmt[$sUtilID])),abs($aUtiltzGmt[$sUtilID])), $aUtiltzLibelle[$sUtilID])."</SMALL></SPAN>";
      else
        $styleUTC = "";

      $sOutput .= "  <TR height=\"17\">\n";
      // Pour chaque utilisateur on verifie si on a acces a son planning en consultation
      $DB_CX->DbQuery("SELECT util_id FROM ${PREFIX_TABLE}utilisateur LEFT JOIN ${PREFIX_TABLE}planning_partage ON ppl_util_id=util_id WHERE util_id=".$sUtilID." AND (util_partage_planning='1' OR (util_partage_planning='2' AND ppl_consultant_id=".$idUser."))");
      if (($DB_CX->DbNumRows() && ($droit_AGENDAS < _DROIT_AGENDA_TOUS)) || ($droit_AGENDAS >= _DROIT_AGENDA_TOUS) || $sUtilID==$idUser) {
        $sOutput .= "    <TD class=\"nomUtil\" style=\"padding-left:3px;padding-right:3px;\" height=\"43\" valign=\"middle\"><A href=\"javascript: substUser('".$sUtilID."');\" title=\"".trad("PLGL_PLANNING_UTIL")."\">".$sNomUtil."</A>".$styleUTC."</TD>\n";
        $autoriseConsult = true;
      } else {
        $sOutput .= "    <TD class=\"nomUtil\" style=\"font-weight:normal;font-style:italic;padding-left:3px;padding-right:3px;\" height=\"43\" valign=\"middle\">".$sNomUtil.$styleUTC."</TD>\n";
        $autoriseConsult = false;
      }
      $aHeureDebutJourneeUtilTmp=$aHeureDebutJourneeUtil[$sUtilID];
      if ($autoriseConsult || ($ckAffCache=="O") || $VisuPl) {
        for ($Jsem=0;$Jsem<7;$Jsem++) {
          if (substr($vSemaineType[$sUtilID],$Jsem,1)=="0") {
            // Journee hors profil semaine type => indisponibilite toute la journee
            $aHeureDebutJourneeUtil[$sUtilID] = $zlHF;$JourType=true;
          } else {
            $aHeureDebutJourneeUtil[$sUtilID] = $aHeureDebutJourneeUtilTmp;
            $JourType=false;
          }
          $numJour= $premierJourSemaine+$Jsem;
          $dateCrt = mktime(12,0,0,$moisEnCours,$numJour,$anneeEnCours);
          //Preparation au decalage horaire
          $UtilDecalageHoraire = calculDecalageH($aUtiltzGmt[$sUtilID],$aUtiltzEte[$sUtilID],$aUtiltzHiver[$sUtilID],$dateCrt);
          $aDecalageHoraire = calculDecalageH($tzGmt,$tzEte,$tzHiver,$dateCrt);
          $aJournee[1][$Jsem][6] = $aUtilDecalageHoraire[$sUtilID] - $UtilDecalageHoraire + $aDecalageHoraire - $decalageHoraire;
          if ($AffTZ=="O") {
            $aJournee[1][$Jsem][7] = $UtilDecalageHoraire - $aDecalageHoraire;
          } else {
            $aJournee[1][$Jsem][7] = 0;
          }
          $age_date="DATE_FORMAT((age_date + INTERVAL (age_heure_debut+$aDecalageHoraire)*60 MINUTE),'%Y-%m-%d')";
          $age_dateAvant="DATE_FORMAT((age_date + INTERVAL (age_heure_debut+$aDecalageHoraire+24)*60 MINUTE),'%Y-%m-%d')";
          $age_heure_debut = "((age_heure_debut+".($aDecalageHoraire+24).")-FLOOR((age_heure_debut+".($aDecalageHoraire+24).")/24)*24)";
          $age_heure_fin = "((age_heure_fin+".($aDecalageHoraire+24).")-FLOOR((age_heure_fin+".($aDecalageHoraire+24).")/24)*24)";

          // Recuperation des horaires des notes dans la table {PREFIX_TABLE}agenda_concerne
          $sql  = "SELECT age_heure_debut, age_heure_fin, age_aty_id, age_libelle, age_detail, age_couleur, age_util_id,";
          $sql .= "       age_createur_id, CONCAT(".$NOM_UTIL_CREATEUR.") AS nomCreateur, age_prive, age_date_creation,";
          $sql .= "       age_date_modif, age_modificateur_id, CONCAT(".$NOM_UTIL_MODIFICATEUR.") AS nomModificateur, age_id,";
          $sql .= "       age_lieu, CONCAT(".$FORMAT_NOM_CONTACT.") AS nomContact, $age_date AS dateNote, age_rappel, ";
          $sql .= "       age_rappel_coeff, age_mere_id, age_ape_id, cal_id, aco_termine, cal_partage, age_nb_participant";
          $sql .= " FROM ${PREFIX_TABLE}agenda_concerne, ${PREFIX_TABLE}agenda LEFT JOIN ${PREFIX_TABLE}calepin ON cal_id=age_cal_id, ${PREFIX_TABLE}utilisateur t1, ${PREFIX_TABLE}utilisateur t2";
          $sql .= " WHERE aco_util_id=".$sUtilID;
          $sql .= "  AND age_id=aco_age_id";
          $sql .= "  AND ($age_date='".date("Y-m-d",$dateCrt)."' OR ($age_dateAvant='".date("Y-m-d",$dateCrt)."' AND $age_heure_debut>=$age_heure_fin AND $age_heure_fin!=0))";

          $sql .= "  AND age_disponibilite=0";
          $sql .= "  AND t1.util_id=age_createur_id AND t2.util_id=age_modificateur_id";
          $sql .= $whereCouleur;
          $sql .= " ORDER BY age_aty_id DESC, age_date, age_heure_debut ASC, age_heure_fin DESC";
          $DB_CX->DbQuery($sql);

          // Remplissage du tableau de la journee a 1 (occupe) ou 2 (hors profil)
          while ($enr=$DB_CX->DbNextRow()) {
            //Recuperation des droits de l'utilisateur sur la note
            $AutoAffectT=(($aAutoAffect[$sUtilID]==1) ? true : false);
            attributDroits($enr, $droitModifStatut, $droitModifNotePerso, $droitModifNoteAffectee, $droitSuppOcc, $droitSuppNoteCreee, $droitSuppNoteAffectee, $droitApprNote, $sUtilID, $AutoAffectT);
            //Decalage des notes en fonction du fuseau horaire
            if ($AffTZ=="O") {
              $Stk_debr=$enr['age_heure_debut'];
              $Stk_finr=$enr['age_heure_fin'];
              list($Stk_debr,$Stk_finr,$enr['dateCreation'],$enr['dateModif']) = decaleNote($aUtiltzGmt[$sUtilID],$aUtiltzEte[$sUtilID],$aUtiltzHiver[$sUtilID],date("Y-m-d",$dateCrt),$enr['dateNote'],$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
            }
            list($enr['age_heure_debut'],$enr['age_heure_fin'],$enr['dateCreation'],$enr['dateModif']) = decaleNote($tzGmt,$tzEte,$tzHiver,date("Y-m-d",$dateCrt),$enr['dateNote'],$enr['age_heure_debut'],$enr['age_heure_fin'],$enr['age_date_creation'],$enr['age_date_modif']);
            // Ajustement des heures de debut et de fin si hors profil ou pour les notes couvrant toute une journee
            $notePrive=false;
            if ($enr['age_aty_id']==3) {
              $enr['age_heure_debut']=$aHeureDebutJourneeUtil[$sUtilID];
              $enr['age_heure_fin']=$aHeureFinJourneeUtil[$sUtilID];
              $finr = $enr['age_heure_fin'];
              $hprf0=false;
              $hprf1=false;
            } else {
              $debr=$enr['age_heure_debut'];
              $enr['age_heure_debut']=max($aHeureDebutJourneeUtil[$sUtilID],$enr['age_heure_debut']);
              $finr=$enr['age_heure_fin'];
              $enr['age_heure_fin']=min($aHeureFinJourneeUtil[$sUtilID],$enr['age_heure_fin']);
              if ($finr>$enr['age_heure_fin'] || $debr>$enr['age_heure_fin']) {
                $enr['age_heure_debut']=min($debr,$iHeureMax-1);
                $enr['age_heure_fin']=min($finr,$iHeureMax);
                $hprf0=true;
                $PfNote=true;
              } else
                $hprf0=false;
              if ($finr<$enr['age_heure_debut'] || $debr<$enr['age_heure_debut']) {
                $enr['age_heure_fin']=max($enr['age_heure_debut'],$finr);
                $enr['age_heure_debut']=max($iHeureMin,$debr);
                $hprf1=true;
                $PfNote=true;
              } else
                $hprf1=false;
              if ($aUtilDoubleJours[$sUtilID]) {
                if ($hprf0 || $hprf1) {
                  $hprf0=$PfNote=$hprf1=false;
                } else {
                  $hprf0=$PfNote=$hprf1=true;
                }
                $enr['age_heure_fin']=$finr;
              }
            }
            //Propriete Privee ou Publique de la note
            if (!$autoriseConsult || ($sUtilID!=$idUser && $enr['age_util_id']!=$idUser && $enr['age_prive']==1)) {
              $enr['age_libelle'] = "&nbsp;".trad("COMMUN_OCCUPE");
              $enr['age_detail'] = "&nbsp;"; // Detail et info de creation non visible si note privee
              $enr['age_couleur'] = $PlanningNotePrivee; // Couleur de note non visible si note privee
              $enr['age_lieu'] = ""; // Emplacement non visible  si note privee
              $notePrive=true;
            } else {
              //Info sur le contact associe
              if (!empty($enr['nomContact'])) {
                if ($sUtilID==$idUser || $enr['cal_partage'] =='O')
                  $enr['age_detail'] = "<DIV style=\"background-color:".$AgendaContactPopup.";\"><A href=\"javascript: affContact('".$enr['cal_id']."');\"><IMG src=\"image/contact.gif\" width=\"10\" height=\"11\" border=\"0\" align=\"absmiddle\" title=\"".trad("PLGL_INFO_ASSOCIE")."\"></A> : <B>".$enr['nomContact']."</B></DIV>".$enr['age_detail'];
                else
                  $enr['age_detail'] = "<DIV style=\"background-color:".$AgendaContactPopup.";\"><IMG src=\"image/contact.gif\" width=\"10\" height=\"11\" border=\"0\" align=\"absmiddle\" title=\"".trad("PLGL_INFO_ASSOCIE")."\"></DIV>".$enr['age_detail'];
              }
              //Info sur la creation / modification de la note
              afficheInfoModifNote($enr, $sUtilID);
            }
            // Couleur de fond de la note si non definie dans la bdd
            if (empty($enr['age_couleur']))
              $enr['age_couleur'] = ($enr['age_util_id']==$sUtilID) ? $AgendaFondNotePerso : $AgendaFondNote;
            // Plage horaire de la note
            if ($AffTZ=="O") {
              $plageNote = ($enr['age_aty_id']==2) ? afficheHeure(floor($Stk_debr),$Stk_debr,$formatHeure)."&rsaquo;".afficheHeure(floor($Stk_finr),$Stk_finr,$formatHeure) : trad('COMMUN_JOURNEE_ENTIERE');
            } else {
              $plageNote = ($enr['age_aty_id']==2) ? afficheHeure(floor($debr),$debr,$formatHeure)."&rsaquo;".afficheHeure(floor($finr),$finr,$formatHeure) : trad('COMMUN_JOURNEE_ENTIERE');
            }
            // Info a afficher dans le popup
            $libelleNote = htmlspecialchars($enr['age_libelle']).((!empty($enr['age_lieu'])) ? "<BR><I>(".$enr['age_lieu'].")</I>" : "");
            $detailNote = htmlspecialchars(nlTObr($enr['age_detail']));
            $noteCrt = 0;
            $noteCpt = 0;
            $olibelleNote="";
            $odetailNote="";
            $oiCell="";
            for ($i=floor($enr['age_heure_debut']); $i<min($finr,$enr['age_heure_fin']); $i+=0.25) {
              // Si precision 30mn et note deja commence a etre affichee (pour afficher les notes commencant a la 15 ou 45eme mn) et si on est sur une tranche 15 ou 45
              if ($noteCrt!=$enr['age_id'] || ($noteCrt==$enr['age_id'] && (($i-floor($i)==0) || ($i-floor($i)==0.25) || ($i-floor($i)==0.5) || ($i-floor($i)==0.75)))) {
                // Indice de la cellule du tableau a manipuler
                $iCell = floor($i-floor($iHeureMin));
                if ($oiCell != $iCell) {
                  $olibelleNote="";
                  $odetailNote="";
                }
                if ($olibelleNote != $libelleNote && $odetailNote != $detailNote) {
                  $aJournee[$iCell][$Jsem][3]=$enr[age_id];
                  // Saut de ligne si plusieurs notes sur la meme case
                  $aJournee[$iCell][$Jsem][2] = ($aJournee[$iCell][$Jsem][0] != "0") ? "*" : "&nbsp;";

                  if ($aJournee[$iCell][$Jsem][2]=="*") {
                    $PlColor=true;
                    $PlNote=true;
                    $aJournee[$iCell][$Jsem][5]++;
                    if ($aJournee[$iCell][$Jsem][5]<10)
                      $aJournee[$iCell][$Jsem][2] = $aJournee[$iCell][$Jsem][5];
                  } else
                    $PlColor=false;
                  if (($hprf0 == true || $hprf1 == true) && $PlColor==true)
                    $aJournee[$iCell][$Jsem][2]="H<br>*";
                  if (($hprf0 == true || $hprf1 == true) && $PlColor==false)
                    $aJournee[$iCell][$Jsem][2]="H";
                  // Contenu du popup
                  if (($sUtilID==$idUser) || (!$notePrive && ($AutoAffectT || ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR)))) {
                    $lien="";
                    if ($enr['aco_termine'] == 1) {
                      $styleNote = ($NOTE_BARREE) ? "line-through" : "none";
                      $TitleTemoin = trad("AGENDA_LEGENDE_NOTE_TERMINEE");
                      $imgTemoin = "puce_ok.gif";
                    } else {
                      $styleNote = "none";
                      $TitleTemoin = trad("AGENDA_LEGENDE_NOTE_ACTIVE");
                      $imgTemoin = "puce_ko.gif";
                    }
                    $puce = "&nbsp;<IMG src='image/".$imgTemoin."' width='6' height='6' border='0' title='".$TitleTemoin."'>&nbsp;";
                    // Indique si un rappel a ete programme
                    if ($enr['age_rappel']>0) {
                      $rappel = trad('COMMUN_RAPPEL')." ".$enr['age_rappel']." ";
                      if ($enr['age_rappel_coeff']==1)
                        $rappel .= trad('COMMUN_MINUTE');
                      elseif ($enr['age_rappel_coeff']==60)
                        $rappel .= trad('COMMUN_HEURE');
                      else
                        $rappel .= trad('COMMUN_JOUR');
                      $rappel .= " ".trad('COMMUN_AVANCE');
                      $lien .= "<IMG src='image/rappel.gif' border='0' align='absmiddle' title='".$rappel."'>&nbsp;";
                    }
                    // Modification d'une note
                    $lien .= ($droitModifNotePerso || $droitModifNoteAffectee) ? "<A href='javascript: affNoteG(".$aJournee[$iCell][$Jsem][3].",".$sUtilID.");'><IMG src='image/modif.gif' width='13' height='13' border='0' vspace='1' align='absmiddle' title='".trad('PLGL_MOD_NOTE')."'></A>" : "";
                    // Appropriation d'une note
                    $lien .= ($droitApprNote) ? "<A href='javascript: apprNote(".$enr['age_id'].");'><IMG src='image/appropriation.gif' border='0' align='absmiddle' title='".trad('PLGL_APPROPRIATION')."'></A>" : "";
                    // Suppression d'une note
                   if ($enr['age_ape_id']!=1) {
                      if ($droitSuppNoteCreee)
                        $lien .= "&nbsp;<A href='javascript: supprOcc(".$enr['age_id'].",0);'><IMG src='image/recurrent.gif' width='13' height='11' border='0' align='absmiddle' title='".trad('COMMUN_SUPPR_OCCURENCE')."'></A>";
                      else
                        $lien .= "&nbsp;<IMG src='image/recurrent.gif' width='13' height='11' border='0' align='absmiddle' title='".trad('COMMUN_NOTE_RECURRENTE')."'></A>";
                    }
                    // Suppression d'une occurence
                    if ($droitSuppNoteCreee)
                      $lien .= "&nbsp;<A href='javascript: supprOcc(".(($enr['age_mere_id']) ? $enr['age_mere_id'] : $enr['age_id']).",1);'><IMG src='image/suppr.gif' width='12' height='12' border='0' align='absmiddle' title='".trad('COMMUN_SUPPR_NOTE')."'></A>";
                    elseif ($droitSuppNoteAffectee)
                      $lien .= "&nbsp;<A href='javascript: supprOcc(".$enr['age_id'].",2);'><IMG src='image/suppr.gif' width='12' height='12' border='0' align='absmiddle' title='".trad('COMMUN_SUPPR_NOTE')."'></A>";

                    $aJournee[$iCell][$Jsem][4] = (($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR) ? false : true);
                  } else {
                    $aJournee[$iCell][$Jsem][4]=true;
                    $lien ="";
                  }
                  $aJournee[$iCell][$Jsem][0] .= "<TABLE width='100%' style='BORDER-TOP: solid 1px ".$AgendaBordureTableau."; BORDER-BOTTOM:solid 1px ".$AgendaBordureTableau.";' cellpadding=0 cellspacing=0><TR style='background-color:".$enr['age_couleur']."' class='PopUpGbl'><TD height='13' valign='top' nowrap><B>".$plageNote."</B>&nbsp;:".$puce."</TD><TD width='100%'><span style='text-decoration: ".$styleNote.";'><A style='font-weight:normal'>".$libelleNote."</SPAN></TD><TD nowrap>".$lien."</TD></TR></TABLE><TABLE width='100%' border=0 cellpadding=2 cellspacing=0><TR><TD class='ibTexte'>".$detailNote."</TD></TR></TABLE>";
                  // Couleur de la case
                  $olibelleNote=$libelleNote;
                  $odetailNote=$detailNote;
                  $oiCell=$iCell;
                  $aJournee[$iCell][$Jsem][1] = $enr['age_couleur'];
                  if ($PlColor==true)
                    $aJournee[$iCell][$Jsem][1]=$PlanningPartiel;
                  if ($hprf0 == true || $hprf1 == true)
                    $aJournee[$iCell][$Jsem][1]=$AgendaBordureTableau;
                  // Note en cours de lecture
                  $noteCrt = $enr['age_id'];
                }
              } else {
                $noteCrt = 0;
              }
            }
          }
          // Affichage de la journee de l'utilisateur
          if (substr($SemaineTypeTotal,$Jsem,1)=="1") {
            if (($sUtilID != $idUser) and $aJournee[1][$Jsem][6] < 0) {
              $decalHP1=$aJournee[1][$Jsem][6];
            } else {
              $decalHP1=0;
            }
            if (($sUtilID != $idUser) and $aJournee[1][$Jsem][6] > 0) {
              $decalHP2=$aJournee[1][$Jsem][6];
            } else {
              $decalHP2=0;
            }
            if (!$aUtilDoubleJours[$sUtilID]) {
              for ($i=($iHeureMin+$decalHP1-1); $i<floor($aHeureDebutJourneeUtil[$sUtilID]+$decalHP1); $i++)
                if ($aJournee[floor($i-$iHeureMin)][$Jsem][0]=="0")
                  $aJournee[floor($i-$iHeureMin)][$Jsem][0]="2";
              for ($i=floor($aHeureFinJourneeUtil[$sUtilID]+$decalHP1+0.75); $i<=ceil($iHeureMax+$decalHP2); $i++)
                if ($aJournee[floor($i-$iHeureMin)][$Jsem][0]=="0")
                  $aJournee[floor($i-$iHeureMin)][$Jsem][0]="2";
            } else {
              for ($i=floor($aHeureDebutJourneeUtil[$sUtilID]+$decalHP1); $i<=ceil($aHeureFinJourneeUtil[$sUtilID]+$decalHP1); $i++)
                if ($aJournee[floor($i-$iHeureMin)][$Jsem][0]=="0")
                  $aJournee[floor($i-$iHeureMin)][$Jsem][0]="2";
            }
            $OldClass="";
            for ($i=0;$i<$iDureeJournee;$i++) {
              if (($sUtilID==$idUser && !$aJournee[$i][$Jsem][4]) || (!$aJournee[$i][$Jsem][4] && (($aAutoAffect[$sUtilID]==1) || ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION and $droit_AGENDAS >= _DROIT_AGENDA_TOUS)))) {
                $lienNote=" onclick=\"javascript: stc1('".trad('PLGL_LEGENDE_DETAIL')."','".addslashes(substr($aJournee[$i][$Jsem][0],1))."'); return false;\"";
                $cursor = "pointer ";
              } else {
                $lienNote = "";
                $cursor = "default";
              }
              if ($aJournee[$i][$Jsem][0]!="0") {
                $Class = ($aJournee[$i][$Jsem][0]==$aJournee[$i+1][$Jsem][0]) ? (($aJournee[$i][$Jsem][0]==$OldClass) ? "jourPlanningGbl" : "DjourPlanningGbl") : (($aJournee[$i][$Jsem][0]==$OldClass) ? "FjourPlanningGbl" : "jourPlanning");
              }
              $OldClass=$aJournee[$i][$Jsem][0];
              if ($aJournee[$i][$Jsem][0]!="2" && $aJournee[$i][$Jsem][0]!="0") {
                $popup = $lienNote." onmouseover=\"javascript: mtc1('".trad('PLGL_LEGENDE_DETAIL')."','".addslashes(substr($aJournee[$i][$Jsem][0],1))."'); return false;\" onmouseout=\"javascript: nd(); return true;\"";
                $sOutput .= "    <TD class=\"".$Class."\" style=\"cursor:".$cursor."; color:".$PlanningInvalideTexte."; background-color:".$aJournee[$i][$Jsem][1].";\"".$popup.">".$aJournee[$i][$Jsem][2]."</TD>"; //.$colspan
                // Reinitialisation du tableau pour le jour suivant
              } elseif ($aJournee[$i][$Jsem][0]=="2") {
                if ($aJournee[$i][$Jsem][2]=="H") {
                  $popup = $lienNote." onmouseover=\"javascript: mtc1('".trad('PLGL_LEGENDE_DETAIL')."','".addslashes(substr($aJournee[$i][$Jsem][0],1))."'); return false;\" onmouseout=\"javascript: nd(); return true;\"";
                  $sOutput .= "    <TD class=\"".$Class."\" style=\"cursor:default; color:".$PlanningInvalideTexte."; background-color:".$AgendaBordureTableau.";\"".$popup.">".$aJournee[$i][$Jsem][2]."</TD>"; //.$colspan
                } else {
                  $sOutput .= "    <TD class=\"".$Class."\" style=\"cursor:default; color:".$PlanningInvalideTexte."; background-color:".$AgendaBordureTableau.";\">&nbsp;</TD>\n";
                }
              } else {
                $hFin = colSpanH($i+1,$aJournee[$i][$Jsem][0]);
                $Class = ($hFin<$iDureeJournee) ? "jourPlanningGbl" : "FjourPlanningGbl";
                if ($droit_NOTES >= _DROIT_NOTE_STANDARD_SANS_APPR && (($sUtilID==$idUser) || (($aAutoAffect[$sUtilID]==1) || ($droit_NOTES >= _DROIT_NOTE_MODIF_CREATION and $droit_AGENDAS >= _DROIT_AGENDA_TOUS)))) {
                  $cursor="pointer ";
                  $sd1 = mktime(12,0,0,$moisEnCours,$premierJourSemaine+$Jsem,$anneeEnCours);
                  $T_Note=(floor($i+$iHeureMin+$aJournee[1][$Jsem][7]) < ($aHeureDebutJourneeUtil[$sUtilID]+$aJournee[1][$Jsem][7]) ? ($aHeureDebutJourneeUtil[$sUtilID]+$aJournee[1][$Jsem][7]) : floor($i+$iHeureMin+$aJournee[1][$Jsem][7]));
                  $sOutput .= "    <TD class=\"".$Class."\" style=\"cursor:".$cursor."; background:".$bgColor[$i%2]."\" onclick=\"javascript: nvNoteG('".$sd1."','".$T_Note."','','".$sUtilID."','".(($AffTZ=="O") ? $sUtilID : "")."');\" title=\"".sprintf(trad("PLGL_CREER_NOTE"), afficheHeure($T_Note,$T_Note))."\">&nbsp;</TD>\n";
                } else {
                  $lienNote="";
                  $cursor="default";
                  $sOutput .= "    <TD class=\"".$Class."\" style=\"cursor:".$cursor."; background:".$bgColor[$i%2]."\">&nbsp;</TD>\n";
                }
                // On avance l'indice de la boucle
                $i = $hFin-1;
              }
              // Reinitialisation du tableau pour le jour suivant
              $aJournee[$i][$Jsem][0]="0";
              $aJournee[$i][$Jsem][1]="";
              $aJournee[$i][$Jsem][2]="&nbsp;";
              $aJournee[$i][$Jsem][3]="";
              $aJournee[$i][$Jsem][4]=false;
              $aJournee[$i][$Jsem][5]=1;
            }
          }
        }
        if ($ckAffCache=="O") {
          $sOutput .= "  </TR>\n";
        } elseif (!$autoriseConsult) {
          $sOutput .= "  </TR>\n";
        }
      }
    }
    echo $sOutput;

    echo "  </TABLE>\n";
    if ($AffTZ=="O")
      echo "  <DIV class=\"timezone\">".sprintf(trad("PLGL_FUSEAU_ACTUEL"), (($tzGmt<0) ? "-" : "+").afficheHeure(floor(abs($tzGmt)),abs($tzGmt)), $tzLibelle)."</DIV>\n";
    else
      echo "  <DIV class=\"timezone\">".sprintf(trad("COMMUN_FUSEAU_ACTUEL"), (($tzGmt<0) ? "-" : "+").afficheHeure(floor(abs($tzGmt)),abs($tzGmt)), $tzLibelle)."</DIV>\n";
    echo ("  <BR>
  <TABLE cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" border=\"0\">
  <TR align=\"center\" height=\"20\">
    <TD height=\"28\" align=\"center\"><TABLE cellspacing=\"0\" cellpadding=\"0\" border=\"0\">
      <TR>
        <TD height=\"4\"><IMG src=\"image/trans.gif\" width=\"1\" height=\"4\" alt=\"\" border=\"0\"></TD>
      </TR>
      <TR>
        <TD class=\"bordTLRB\" bgcolor=\"".$CalJourSelection."\" align=\"center\" width=\"90\" nowrap>".trad("PLGL_JOUR_COURANT")."</TD>
        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"bordTLRB\" bgcolor=\"".$CalJourFerie."\" align=\"center\" width=\"90\" nowrap>".trad("PLGL_JOUR_FERIE")."</TD>
        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"bordTLRB\" bgcolor=\"".$CalJourEvenement."\" align=\"center\" width=\"90\" nowrap>".trad("COMMUN_EVENEMENT")."</TD>
        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"bordTLRB\" bgcolor=\"".$PlanningJour."\" style=\"color:".$CalJourWE."\" align=\"center\" width=\"110\" nowrap>".(($SEMAINE_CALENDRIER=="0111110") ? "".trad("PLGL_WEEKEND")."" : "".trad("PLGL_HORS_SEMAINE")."")."</TD>");
    if ($PlNote==true) {
      echo ("        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"bordTLRB\" style=\"cursor:default; color:".$PlanningInvalideTexte."; background-color:".$PlanningPartiel.";\" align=\"center\" width=\"110\" nowrap>".trad('PLGL_PL_NOTES')."");
    }
    if ($PfNote==true) {
      echo ("        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"bordTLRB\" style=\"cursor:default; color:".$PlanningInvalideTexte."; background-color:".$AgendaBordureTableau.";\" align=\"center\" width=\"130\" nowrap>".trad('PLGL_HPROFIL_N')."</TD>");
    } else {
      echo ("        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
       <TD class=\"bordTLRB\" style=\"cursor:default; color:".$PlanningInvalideTexte."; background-color:".$AgendaBordureTableau.";\" align=\"center\" width=\"130\" nowrap>".trad('PLGL_HPROFIL')."</TD>");
    }
    echo ("         <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"legendeBis\"><IMG src=\"image/puce_ko.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_ACTIVE")."</TD>
        <TD>&nbsp;&nbsp;&nbsp;&nbsp;</TD>
        <TD class=\"legendeBis\"><IMG src=\"image/puce_ok.gif\" width=\"6\" height=\"6\" alt=\"\" border=\"0\" align=\"absmiddle\">&nbsp;".trad("AGENDA_LEGENDE_NOTE_TERMINEE")."</TD>
      </TR>
    </TABLE></TD>
  </TR>
  </TABLE>\n");
?>
      <BR>
      <BR>
      <TABLE cellspacing="0" cellpadding="0" width="100%" border="0">
      <TR align="center">
        <TD height="20" colspan="2" class="legende">&nbsp;<IMG src="image/rappel.gif" alt="" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_NOTE_RAPPEL'); ?>&nbsp;&nbsp;&nbsp;<IMG src="image/modif.gif" width="13" height="13" alt="" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_MOD_NOTE'); ?>&nbsp;&nbsp;&nbsp;<IMG src="image/recurrent.gif" width="13" height="11" alt="" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_SUP_OCCURENCE'); ?>&nbsp;&nbsp;&nbsp;<IMG src="image/suppr.gif" alt="" width="12" height="12" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_SUP_NOTE'); ?>&nbsp;&nbsp;&nbsp;<IMG src="image/appropriation.gif" alt="" width="13" height="11" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_APPROPRIER_NOTE'); ?>&nbsp;&nbsp;<IMG src="image/contact.gif" alt="" width="10" height="11" border="0" align="absmiddle">&nbsp;<?php echo trad('PLGL_LEGENDE_CONTACT_ASSOCIE'); ?></TD>
      </TR>
    </TABLE>
  <BR>
<?php
  }
  // FIN SI la liste contenant les identifiants des utilisateurs selectionnes n'est pas vide
  $sChoix="";
?>
<!-- FIN MODULE PLANNING HEBDOMADAIRE GLOBAL -->
