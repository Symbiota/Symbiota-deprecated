<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

//Used by /collections/misc/collprofiles.php page
class OccurrenceCollectionProfile {

	private $conn;
	private $collid;
	private $errorStr;
    private $organizationKey;
    private $installationKey;
    private $datasetKey;
    private $endpointKey;
    private $idigbioKey;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollid($collid){
		if($collid && is_numeric($collid)){
			$this->collid = $this->cleanInStr($collid);
			return true;
		}
		return false;
	}

	public function getCollectionData($filterForForm = 0){
		$returnArr = Array();
		if($this->collid){
			$sql = "SELECT c.institutioncode, i.InstitutionName, ".
				"i.Address1, i.Address2, i.City, i.StateProvince, i.PostalCode, i.Country, i.Phone, ".
				"c.collid, c.CollectionCode, c.CollectionName, ".
				"c.FullDescription, c.Homepage, c.individualurl, c.Contact, c.email, ".
				"c.latitudedecimal, c.longitudedecimal, c.icon, c.colltype, c.managementtype, c.publicedits, ".
				"c.guidtarget, c.rights, c.rightsholder, c.accessrights, c.sortseq, cs.uploaddate, ".
				"IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
				"IFNULL(cs.familycnt,0) AS familycnt, IFNULL(cs.genuscnt,0) AS genuscnt, IFNULL(cs.speciescnt,0) AS speciescnt, ".
				"cs.dynamicProperties, c.securitykey, c.collectionguid ".
				"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
				"LEFT JOIN institutions i ON c.iid = i.iid ".
				"WHERE (c.collid = ".$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['collectioncode'] = $row->CollectionCode;
				$returnArr['collectionname'] = $row->CollectionName;
				$returnArr['institutionname'] = $row->InstitutionName;
				$returnArr['address2'] = $row->Address1;
				$returnArr['address1'] = $row->Address2;
				$returnArr['city'] = $row->City;
				$returnArr['stateprovince'] = $row->StateProvince;
				$returnArr['postalcode'] = $row->PostalCode;
				$returnArr['country'] = $row->Country;
				$returnArr['phone'] = $row->Phone;
				$returnArr['fulldescription'] = $row->FullDescription;
				$returnArr['homepage'] = $row->Homepage;
				$returnArr['individualurl'] = $row->individualurl;
				$returnArr['contact'] = $row->Contact;
				$returnArr['email'] = $row->email;
				$returnArr['latitudedecimal'] = $row->latitudedecimal;
				$returnArr['longitudedecimal'] = $row->longitudedecimal;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
				$returnArr['publicedits'] = $row->publicedits;
				$returnArr['guidtarget'] = $row->guidtarget;
				$returnArr['rights'] = $row->rights;
				$returnArr['rightsholder'] = $row->rightsholder;
				$returnArr['accessrights'] = $row->accessrights;
				$returnArr['sortseq'] = $row->sortseq;
				$returnArr['skey'] = $row->securitykey;
				$returnArr['guid'] = $row->collectionguid;
				$uDate = "";
				if($row->uploaddate){
					$uDate = $row->uploaddate;
					$month = substr($uDate,5,2);
					$day = substr($uDate,8,2);
					$year = substr($uDate,0,4);
					$uDate = date("j F Y",mktime(0,0,0,$month,$day,$year));
				}
				$returnArr['uploaddate'] = $uDate;
				$returnArr['recordcnt'] = $row->recordcnt;
				$returnArr['georefcnt'] = $row->georefcnt;
				$returnArr['familycnt'] = $row->familycnt;
				$returnArr['genuscnt'] = $row->genuscnt;
				$returnArr['speciescnt'] = $row->speciescnt;
				$returnArr['dynamicProperties'] = $row->dynamicProperties;
			}
			$rs->free();
			//Get categories
			$sql = 'SELECT ccpk '.
				'FROM omcollcatlink '.
				'WHERE (collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['ccpk'] = $r->ccpk;
			}
			$rs->free();
			//Get additional statistics
			/*$sql = 'SELECT count(DISTINCT o.occid) as imgcnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['imgpercent'] = ($returnArr['recordcnt']?round(($row->imgcnt/$returnArr['recordcnt'])*100):0);
			}
			$rs->free();
			//BOLD count
			$sql = 'SELECT count(g.occid) as boldcnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.boldsystems%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['boldcnt'] = $r->boldcnt;
			}
			$rs->free();
			//GenBank count
			$sql = 'SELECT count(g.occid) as gencnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.ncbi%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['gencnt'] = $r->gencnt;
			}
			$rs->free();
			//Reference count
			$sql = 'SELECT count(r.occid) as refcnt '.
				'FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['refcnt'] = $r->refcnt;
			}
			$rs->free();*/
			//Check to make sure Security Key and collection GUIDs exist
			if(!$returnArr['guid']){
				$returnArr['guid'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET collectionguid = "'.$returnArr['guid'].'" '.
					'WHERE collectionguid IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}
			if(!$returnArr['skey']){
				$returnArr['skey'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET securitykey = "'.$returnArr['skey'].'" '.
					'WHERE securitykey IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}
		}
		if($filterForForm){
			$this->cleanOutArr($returnArr);
		}
		return $returnArr;
	}

	public function submitCollEdits($postArr){
        global $GBIF_USERNAME,$GBIF_PASSWORD;
	    $status = true;
		if($this->collid){
			$instCode = $this->cleanInStr($postArr['institutioncode']);
			$collCode = $this->cleanInStr($postArr['collectioncode']);
			$coleName = $this->cleanInStr($postArr['collectionname']);
			$fullDesc = $this->cleanInStr($postArr['fulldescription']);
			$homepage = $this->cleanInStr($postArr['homepage']);
			$contact = $this->cleanInStr($postArr['contact']);
			$email = $this->cleanInStr($postArr['email']);
			$publicEdits = (array_key_exists('publicedits',$postArr)?$postArr['publicedits']:0);
			$gbifPublish = (array_key_exists('publishToGbif',$postArr)?$postArr['publishToGbif']:'NULL');
            $idigPublish = (array_key_exists('publishToIdigbio',$postArr)?$postArr['publishToIdigbio']:'NULL');
			$guidTarget = (array_key_exists('guidtarget',$postArr)?$postArr['guidtarget']:'');
			$rights = $this->cleanInStr($postArr['rights']);
			$rightsHolder = $this->cleanInStr($postArr['rightsholder']);
			$accessRights = $this->cleanInStr($postArr['accessrights']);
			if($_FILES['iconfile']['name']){
				$icon = $this->addIconImageFile();
			}
			else{
				$icon = $this->cleanInStr($postArr['iconurl']);
			}
			$indUrl = $this->cleanInStr($postArr['individualurl']);

			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections '.
				'SET institutioncode = "'.$instCode.'",'.
				'collectioncode = '.($collCode?'"'.$collCode.'"':'NULL').','.
				'collectionname = "'.$coleName.'",'.
				'fulldescription = '.($fullDesc?'"'.$fullDesc.'"':'NULL').','.
				'homepage = '.($homepage?'"'.$homepage.'"':'NULL').','.
				'contact = '.($contact?'"'.$contact.'"':'NULL').','.
				'email = '.($email?'"'.$email.'"':'NULL').','.
				'latitudedecimal = '.($postArr['latitudedecimal']?$postArr['latitudedecimal']:'NULL').','.
				'longitudedecimal = '.($postArr['longitudedecimal']?$postArr['longitudedecimal']:'NULL').',';
            if(array_key_exists('publishToIdigbio',$postArr)){
                $sql .= 'publishToGbif = '.$gbifPublish.','.
                    'publishToIdigbio = '.$idigPublish.',';
            }
            $sql .= 'publicedits = '.$publicEdits.','.
                'guidtarget = '.($guidTarget?'"'.$guidTarget.'"':'NULL').','.
				'rights = '.($rights?'"'.$rights.'"':'NULL').','.
				'rightsholder = '.($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
				'accessrights = '.($accessRights?'"'.$accessRights.'"':'NULL').', '.
				'icon = '.($icon?'"'.$icon.'"':'NULL').', '.
				'individualurl = '.($indUrl?'"'.$indUrl.'"':'NULL').' ';
			if(array_key_exists('colltype',$postArr)){
				$sql .= ',managementtype = "'.$postArr['managementtype'].'",'.
					'colltype = "'.$postArr['colltype'].'",'.
					'sortseq = '.($postArr['sortseq']?$postArr['sortseq']:'NULL').' ';
			}
			$sql .= 'WHERE (collid = '.$this->collid.')';
			//echo $sql; exit;
			if(!$conn->query($sql)){
				$status = 'ERROR updating collection: '.$conn->error;
				return $status;
			}

			//Modify collection category, if needed
			if(isset($postArr['ccpk']) && $postArr['ccpk']){
				$rs = $conn->query('SELECT ccpk FROM omcollcatlink WHERE collid = '.$this->collid);
				if($r = $rs->fetch_object()){
					if($r->ccpk <> $postArr['ccpk']){
						if(!$conn->query('UPDATE omcollcatlink SET ccpk = '.$postArr['ccpk'].' WHERE ccpk = '.$r->ccpk.' AND collid = '.$this->collid)){
							$status = 'ERROR updating collection category link: '.$conn->error;
							return $status;
						}
					}
				}
				else{
					if(!$conn->query('INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$postArr['ccpk'].','.$this->collid.')')){
						$status = 'ERROR inserting collection category link(1): '.$conn->error;
						return $status;
					}
				}
			}
			else{
				$conn->query('DELETE FROM omcollcatlink WHERE collid = '.$this->collid);
			}
			$conn->close();
		}
		return $status;
	}

    public function submitCollAdd($postArr){
		global $symbUid;
		$instCode = $this->cleanInStr($postArr['institutioncode']);
		$collCode = $this->cleanInStr($postArr['collectioncode']);
		$coleName = $this->cleanInStr($postArr['collectionname']);
		$fullDesc = $this->cleanInStr($postArr['fulldescription']);
		$homepage = $this->cleanInStr($postArr['homepage']);
		$contact = $this->cleanInStr($postArr['contact']);
		$email = $this->cleanInStr($postArr['email']);
		$rights = $this->cleanInStr($postArr['rights']);
		$rightsHolder = $this->cleanInStr($postArr['rightsholder']);
		$accessRights = $this->cleanInStr($postArr['accessrights']);
		$publicEdits = (array_key_exists('publicedits',$postArr)?$postArr['publicedits']:0);
        $gbifPublish = (array_key_exists('publishToGbif',$postArr)?$postArr['publishToGbif']:0);
        if(array_key_exists('publishToIdigbio',$postArr)){
            $idigPublish = (array_key_exists('publishToIdigbio',$postArr)?$postArr['publishToIdigbio']:0);
        }
        $guidTarget = (array_key_exists('guidtarget',$postArr)?$postArr['guidtarget']:'');
		if($_FILES['iconfile']['name']){
			$icon = $this->addIconImageFile();
		}
		else{
			$icon = array_key_exists('iconurl',$postArr)?$this->cleanInStr($postArr['iconurl']):'';
		}
		$managementType = array_key_exists('managementtype',$postArr)?$this->cleanInStr($postArr['managementtype']):'';
		$collType = array_key_exists('colltype',$postArr)?$this->cleanInStr($postArr['colltype']):'';
		$guid = array_key_exists('collectionguid',$postArr)?$this->cleanInStr($postArr['collectionguid']):'';
		if(!$guid) $guid = UuidFactory::getUuidV4();
		$indUrl = array_key_exists('individualurl',$postArr)?$this->cleanInStr($postArr['individualurl']):'';
		$sortSeq = array_key_exists('sortseq',$postArr)?$postArr['sortseq']:'';

		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omcollections(institutioncode,collectioncode,collectionname,fulldescription,homepage,'.
			'contact,email,latitudedecimal,longitudedecimal,publicedits,publishToGbif,'.
            (array_key_exists('publishToIdigbio',$postArr)?'publishToIdigbio,':'').
            'guidtarget,rights,rightsholder,accessrights,icon,'.
			'managementtype,colltype,collectionguid,individualurl,sortseq) '.
			'VALUES ("'.$instCode.'",'.
			($collCode?'"'.$collCode.'"':'NULL').',"'.
			$coleName.'",'.
			($fullDesc?'"'.$fullDesc.'"':'NULL').','.
			($homepage?'"'.$homepage.'"':'NULL').','.
			($contact?'"'.$contact.'"':'NULL').','.
			($email?'"'.$email.'"':'NULL').','.
			($postArr['latitudedecimal']?$postArr['latitudedecimal']:'NULL').','.
			($postArr['longitudedecimal']?$postArr['longitudedecimal']:'NULL').','.$publicEdits.','.$gbifPublish.','.
            (array_key_exists('publishToIdigbio',$postArr)?$idigPublish.',':'').
			($guidTarget?'"'.$guidTarget.'"':'NULL').','.
			($rights?'"'.$rights.'"':'NULL').','.
			($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
			($accessRights?'"'.$accessRights.'"':'NULL').','.
			($icon?'"'.$icon.'"':'NULL').','.
			($managementType?'"'.$managementType.'"':'Snapshot').','.
			($collType?'"'.$collType.'"':'Preserved Specimens').',"'.
			$guid.'",'.($indUrl?'"'.$indUrl.'"':'NULL').','.
			($sortSeq?$sortSeq:'NULL').') ';
		//echo "<div>$sql</div>";
		$cid = 0;
		if($conn->query($sql)){
			$cid = $conn->insert_id;
			$sql = 'INSERT INTO omcollectionstats(collid,recordcnt,uploadedby) '.
				'VALUES('.$cid.',0,"'.$symbUid.'")';
			$conn->query($sql);
			//Add collection to category
			if(isset($postArr['ccpk']) && $postArr['ccpk']){
				$sql = 'INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$postArr['ccpk'].','.$cid.')';
				if(!$conn->query($sql)){
					$status = 'ERROR inserting collection category link(2): '.$conn->error.'; SQL: '.$sql;
					return $status;
				}
			}
			$this->collid = $cid;
		}
		else{
			$cid = 'ERROR inserting new collection: '.$conn->error;
		}
		$conn->close();
		return $cid;
	}

	public function getAddresses(){
		$retArr = Array();
		if($this->collid){
			$sql = 'SELECT i.iid, i.institutioncode, i.institutionname, i.institutionname2, i.address1, i.address2, '.
				'i.city, i.stateprovince, i.postalcode, i.country, i.phone, i.contact, i.email, i.url, i.notes '.
				'FROM institutions i INNER JOIN omcollections c ON i.iid = c.iid '.
				'WHERE (c.collid = '.$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->iid]['institutioncode'] = $r->institutioncode;
				$retArr[$r->iid]['institutionname'] = $r->institutionname;
				$retArr[$r->iid]['institutionname2'] = $r->institutionname2;
				$retArr[$r->iid]['address1'] = $r->address1;
				$retArr[$r->iid]['address2'] = $r->address2;
				$retArr[$r->iid]['city'] = $r->city;
				$retArr[$r->iid]['stateprovince'] = $r->stateprovince;
				$retArr[$r->iid]['postalcode'] = $r->postalcode;
				$retArr[$r->iid]['country'] = $r->country;
				$retArr[$r->iid]['phone'] = $r->phone;
				$retArr[$r->iid]['contact'] = $r->contact;
				$retArr[$r->iid]['email'] = $r->email;
				$retArr[$r->iid]['url'] = $r->url;
				$retArr[$r->iid]['notes'] = $r->notes;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function linkAddress($addIID){
		$status = false;
		if($this->collid && is_numeric($addIID)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections SET iid = '.$addIID.' WHERE collid = '.$this->collid;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR linking institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	public function removeAddress($removeIID){
		$status = false;
		if($this->collid && is_numeric($removeIID)){
			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections SET iid = NULL '.
				'WHERE collid = '.$this->collid.' AND iid = '.$removeIID;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR removing institution address: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}

	public function updateStatistics($verbose = false){
		$occurMaintenance = new OccurrenceMaintenance();
		if($verbose){
			echo '<ul>';
			$occurMaintenance->setVerbose(true);
			echo '<li>General cleaning in preparation for collecting stats...</li>';
			flush();
			ob_flush();
		}
		$occurMaintenance->generalOccurrenceCleaning($this->collid);
		if($verbose){
			echo '<li>Updating statistics...</li>';
			flush();
			ob_flush();
		}
		$occurMaintenance->updateCollectionStats($this->collid, true);
		if($verbose){
			echo '<li>Finished updating collection statistics</li>';
			flush();
			ob_flush();
		}
	}

    public function triggerGBIFCrawl($datasetKey){
        global $GBIF_USERNAME,$GBIF_PASSWORD;
        $loginStr = $GBIF_USERNAME.':'.$GBIF_PASSWORD;
        $url = 'http://api.gbif.org/v1/dataset/'.$datasetKey.'/crawl';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $loginStr);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json')
        );

        $result = curl_exec($ch);
    }

    public function batchTriggerGBIFCrawl($collIdArr){
        $collIdStr = implode(',',$collIdArr);
        $sql = 'SELECT CollID, publishToGbif, aggKeysStr '.
            'FROM omcollections '.
            'WHERE CollID IN('.$collIdStr.') ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $publishGBIF = $row->publishToGbif;
            $gbifKeyArr = $row->aggKeysStr;
            if($publishGBIF && $gbifKeyArr){
                $gbifKeyArr = json_decode($gbifKeyArr,true);
                if($gbifKeyArr['endpointKey']){
                    $this->triggerGBIFCrawl($gbifKeyArr['datasetKey']);
                }
            }
        }
        $rs->free();
    }

    public function setAggKeys($aggKeyStr){
        $aggKeyArr = json_decode($aggKeyStr,true);
        if($aggKeyArr['organizationKey']){
            $this->organizationKey = $aggKeyArr['organizationKey'];
        }
        if($aggKeyArr['installationKey']){
            $this->installationKey = $aggKeyArr['installationKey'];
        }
        if($aggKeyArr['datasetKey']){
            $this->datasetKey = $aggKeyArr['datasetKey'];
        }
        if($aggKeyArr['endpointKey']){
            $this->endpointKey = $aggKeyArr['endpointKey'];
        }
        if($aggKeyArr['idigbioKey']){
            $this->idigbioKey = $aggKeyArr['idigbioKey'];
        }
    }

    public function updateAggKeys($collId){
        $aggKeyArr = array();
        $status = true;
        $aggKeyArr['organizationKey'] = $this->organizationKey;
        $aggKeyArr['installationKey'] = $this->installationKey;
        $aggKeyArr['datasetKey'] = $this->datasetKey;
        $aggKeyArr['endpointKey'] = $this->endpointKey;
        $aggKeyArr['idigbioKey'] = $this->idigbioKey;
        $aggKeyStr = json_encode($aggKeyArr);
        $conn = MySQLiConnectionFactory::getCon("write");
        $sql = 'UPDATE omcollections '.
            "SET aggKeysStr = '".$aggKeyStr."' ".
            'WHERE (collid = '.$collId.')';
        //echo $sql; exit;
        if(!$conn->query($sql)){
            $status = 'ERROR saving key: '.$conn->error;
            return $status;
        }

        $conn->close();

        return $status;

    }

    public function getInstallationKey(){
        return $this->installationKey;
    }

    public function getDatasetKey(){
        return $this->datasetKey;
    }

    public function getEndpointKey(){
        return $this->endpointKey;
    }

    public function getIdigbioKey(){
        return $this->idigbioKey;
    }

    public function getCollPubArr($collId){
        $returnArr = Array();
        $aggKeyStr = '';
        $sql = 'SELECT CollID, publishToGbif, publishToIdigbio, aggKeysStr, collectionguid '.
            'FROM omcollections '.
            'WHERE CollID IN('.$collId.') ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $returnArr[$row->CollID]['publishToGbif'] = $row->publishToGbif;
            $returnArr[$row->CollID]['publishToIdigbio'] = $row->publishToIdigbio;
            $returnArr[$row->CollID]['collectionguid'] = $row->collectionguid;
            $aggKeyStr = $row->aggKeysStr;
        }
        $rs->free();

        if($aggKeyStr){
            $this->setAggKeys($aggKeyStr);
        }

        return $returnArr;
    }

    public function getGbifInstKey(){
        $returnArr = Array();
        $sql = 'SELECT aggKeysStr '.
            'FROM omcollections '.
            'WHERE aggKeysStr IS NOT NULL ';
        //echo $sql; exit;
        $rs = $this->conn->query($sql);
        while($row = $rs->fetch_object()){
            $returnArr = json_decode($row->aggKeysStr,true);
            if($returnArr['installationKey']){
                return $returnArr['installationKey'];
            }
        }
        $rs->free();

        return '';
    }

    public function findIdigbioKey($guid){
        global $CLIENT_ROOT;
        $url = 'http://search.idigbio.org/v2/search/recordsets?rsq={%22recordids%22:%22';
        $url .= ($_SERVER['HTTPS']?'https://':'http://');
        $url .= $_SERVER['HTTP_HOST'].$CLIENT_ROOT;
        $url .= '/webservices/dwc/'.$guid.'%22}';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $returnArr = json_decode($result,true);

        if(isset($returnArr['items'][0]['uuid'])){
            $this->idigbioKey = $returnArr['items'][0]['uuid'];
        }
        return $this->idigbioKey;
    }

    public function getTaxonCounts($f=''){
		$family = $this->cleanInStr($f);
		$returnArr = Array();
		$sql = '';
		if($family){
			/*
			$sql = 'SELECT t.unitname1 as taxon, Count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'GROUP BY o.CollID, t.unitname1, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family = "'.$family.'") AND (t.unitname1 != "'.$family.'") '.
				'ORDER BY t.unitname1';
			*/
			$sql = 'SELECT t.unitname1 as taxon, Count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Family = "'.$family.'") AND (t.unitname1 != "'.$family.'") '.
				'GROUP BY o.CollID, t.unitname1, o.Family ';
		}
		else{
			/*
			$sql = 'SELECT o.family as taxon, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family IS NOT NULL) AND (o.Family <> "") '.
				'ORDER BY o.Family';
			*/
			$sql = 'SELECT o.family as taxon, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Family IS NOT NULL) AND (o.Family <> "") '.
				'GROUP BY o.CollID, o.Family ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->taxon] = $row->cnt;
		}
		$rs->free();
		asort($returnArr);
		return $returnArr;
	}

	public function getGeographyStats($country,$state){
		$retArr = Array();
		$sql = '';
		if($state){
			$sql = 'SELECT o.county as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') '.($country?'AND (o.country = "'.$this->cleanInStr($country).'") ':'').
				'AND (o.stateprovince = "'.$this->cleanInStr($state).'") AND (o.county IS NOT NULL) '.
				'GROUP BY o.StateProvince, o.county';
		}
		elseif($country){
			$sql = 'SELECT o.stateprovince as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.StateProvince IS NOT NULL) AND (o.country = "'.$this->cleanInStr($country).'") '.
				'GROUP BY o.StateProvince, o.country';
		}
		else{
			$sql = 'SELECT o.country as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.CollID = '.$this->collid.') AND (o.Country IS NOT NULL) '.
				'GROUP BY o.Country ';
		}
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$t = $row->termstr;
			$cnt = $row->cnt;
			if($state){
				$t = trim(str_ireplace(array(' county',' co.',' counties'),'',$t));
				if(array_key_exists($t, $retArr)) $cnt = $cnt + $retArr[$t];
			}
			//if($country) $t = ucwords(strtolower($t));
			if($t) $retArr[$t] = $cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getTaxonomyStats(){
		$retArr = Array();
		$sql = 'SELECT family, count(*) as cnt FROM omoccurrences o  WHERE o.family IS NOT NULL AND collid = '.$this->collid.' GROUP BY family';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[ucwords($r->family)] = $r->cnt;
		}
		$rs->free();
		//ksort($retArr);
		return $retArr;
	}

	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT iid,institutionname,institutioncode '.
			'FROM institutions '.
			'ORDER BY institutionname,institutioncode ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->iid] = $r->institutionname.' ('.$r->institutioncode.')';
		}
		return $retArr;
	}

	public function getCategoryArr(){
		$retArr = array();
		$sql = 'SELECT ccpk, category '.
			'FROM omcollcategories '.
			'ORDER BY category ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->ccpk] = $r->category;
		}
		$rs->free();
		return $retArr;
	}

	public function getCollectionList($full = false){
		$returnArr = Array();
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, '.
			'c.fulldescription, c.homepage, c.contact, c.email, c.icon, c.collectionguid '.
			'FROM omcollections AS c INNER JOIN omcollectionstats AS s ON c.collid = s.collid ';
		if(!$full){
			$sql .= 'WHERE s.recordcnt > 0 ';
			$sql .= 'ORDER BY c.SortSeq,c.CollectionName';
		}
		else{
			$sql .= 'ORDER BY c.CollectionName';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]['institutioncode'] = $row->institutioncode;
			$returnArr[$row->collid]['collectioncode'] = $row->collectioncode;
			$returnArr[$row->collid]['collectionname'] = $row->collectionname;
			$returnArr[$row->collid]['fulldescription'] = $row->fulldescription;
			$returnArr[$row->collid]['homepage'] = $row->homepage;
			$returnArr[$row->collid]['contact'] = $row->contact;
			$returnArr[$row->collid]['email'] = $row->email;
			$returnArr[$row->collid]['icon'] = $row->icon;
			$returnArr[$row->collid]['guid'] = $row->collectionguid;
		}
		$rs->free();
		return $returnArr;
	}

	//Used to index specimen records for particular collection
	public function echoOccurrenceListing($s, $l){
		global $clientRoot;
		$start = $this->cleanInStr($s);
		$limit = $this->cleanInStr($l);
		if(substr($clientRoot,-1) != '/') $clientRoot .= '/';
		if($this->collid){
			//Get count
			$occCnt = 0;
			if(!is_numeric($start)){
				$sql = 'SELECT count(*) AS cnt FROM omoccurrences WHERE collid = '.$this->collid.' ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$occCnt = $r->cnt;
				}
				$rs->free();
				if($occCnt < $limit) $start = 0;
			}

			if(is_numeric($start)){
				$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid, o.sciname, o.recordedby, o.recordnumber, g.guid '.
					'FROM omoccurrences o INNER JOIN guidoccurrences g ON o.occid = g.occid '.
					'WHERE collid = '.$this->collid.' '.
					'ORDER BY o.catalognumber,o.occid '.
					'LIMIT '.$start.','.$limit;
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					echo '<div style="margin:5px;">';
					echo '<div><b>Collector:</b> '.$r->recordedby.' '.$r->recordnumber.'</div>';
					echo '<div style="margin-left:10px;"><b>Scientific Name:</b> '.$r->sciname.'</div>';
					echo '<div style="margin-left:10px;"><b>Identifiers:</b> '.$r->catalognumber.' '.$r->occurrenceid.'</div>';
					//echo '<div style="margin-left:10px;"><b>GUID:</b> '.$r->guid.'</div>';
					echo '<div style="margin-left:10px;"><a href="'.$clientRoot.'/collections/individual/index.php?occid='.$r->occid.'" target="_blank"><b>Full Details</b></a></div>';
					echo '</div>';
				}
				$rs->free();
			}
			else{
				for($j = 0;$j < $occCnt;$j += $limit){
					$endCnt = (($j+$limit)<$occCnt?($j+$limit):$occCnt);
					echo '<div><a href="collectionindex.php?collid='.$this->collid.'&start='.$j.'&limit='.$limit.'">Records '.($j+1).' - '.$endCnt.'</a></div>';
				}
			}
		}
	}

	public function getStatCollectionList($catId = ""){
		//Set collection array
		$collIdArr = array();
		$catIdArr = array();
		if(isset($this->searchTermsArr['db']) && array_key_exists('db',$this->searchTermsArr)){
			$cArr = explode(';',$this->searchTermsArr['db']);
			$collIdArr = explode(',',$cArr[0]);
			if(isset($cArr[1])) $catIdStr = $cArr[1];
		}
		//Set collections
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, c.icon, c.colltype, ccl.ccpk, '.
			'cat.category, cat.icon AS caticon, cat.acronym '.
			'FROM omcollections c LEFT JOIN omcollcatlink ccl ON c.collid = ccl.collid '.
			'LEFT JOIN omcollcategories cat ON ccl.ccpk = cat.ccpk '.
			'ORDER BY ccl.sortsequence, cat.category, c.sortseq, c.CollectionName ';
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		$collArr = array();
		while($r = $result->fetch_object()){
			$collType = '';
			if(stripos($r->colltype, "observation") !== false) $collType = 'obs';
			if(stripos($r->colltype, "specimen")) $collType = 'spec';
			if($collType){
				if($r->ccpk){
					if(!isset($collArr[$collType]['cat'][$r->ccpk]['name'])){
						$collArr[$collType]['cat'][$r->ccpk]['name'] = $r->category;
						$collArr[$collType]['cat'][$r->ccpk]['icon'] = $r->caticon;
						$collArr[$collType]['cat'][$r->ccpk]['acronym'] = $r->acronym;
						//if(in_array($r->ccpk,$catIdArr)) $retArr[$collType]['cat'][$catId]['isselected'] = 1;
					}
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["instcode"] = $r->institutioncode;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collcode"] = $r->collectioncode;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["collname"] = $r->collectionname;
					$collArr[$collType]['cat'][$r->ccpk][$r->collid]["icon"] = $r->icon;
				}
				else{
					$collArr[$collType]['coll'][$r->collid]["instcode"] = $r->institutioncode;
					$collArr[$collType]['coll'][$r->collid]["collcode"] = $r->collectioncode;
					$collArr[$collType]['coll'][$r->collid]["collname"] = $r->collectionname;
					$collArr[$collType]['coll'][$r->collid]["icon"] = $r->icon;
				}
			}
		}
		$result->free();

		$retArr = array();
		//Modify sort so that default catid is first
		if(isset($collArr['spec']['cat'][$catId])){
			$retArr['spec']['cat'][$catId] = $collArr['spec']['cat'][$catId];
			unset($collArr['spec']['cat'][$catId]);
		}
		elseif(isset($collArr['obs']['cat'][$catId])){
			$retArr['obs']['cat'][$catId] = $collArr['obs']['cat'][$catId];
			unset($collArr['obs']['cat'][$catId]);
		}
		foreach($collArr as $t => $tArr){
			foreach($tArr as $g => $gArr){
				foreach($gArr as $id => $idArr){
					$retArr[$t][$g][$id] = $idArr;
				}
			}
		}
		return $retArr;
	}

	public function batchUpdateStatistics($collId){
		echo 'Updating collection statistics...';
		echo '<ul>';
		//echo '<li>General cleaning in preparation for collecting stats... </li>';
		flush();
		ob_flush();
		$occurMaintenance = new OccurrenceMaintenance();
		//$occurMaintenance->generalOccurrenceCleaning();
		$sql = 'SELECT collid, collectionname FROM omcollections WHERE collid IN('.$collId.') ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			echo '<li style="margin-left:15px;">Cleaning statistics for: '.$r->collectionname.'</li>';
			flush();
			ob_flush();
			$occurMaintenance->updateCollectionStats($r->collid, true);
		}
		$rs->free();
		echo '<li>Statistics update complete!</li>';
		echo '</ul>';
		flush();
		ob_flush();
	}

	public function runStatistics($collId){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.CollectionName, IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
			"cs.dynamicProperties ".
			"FROM omcollections AS c INNER JOIN omcollectionstats AS cs ON c.collid = cs.collid ".
			"WHERE c.collid IN(".$collId.") ";
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$returnArr[$r->CollectionName]['collid'] = $r->collid;
			$returnArr[$r->CollectionName]['CollectionName'] = $r->CollectionName;
			$returnArr[$r->CollectionName]['recordcnt'] = $r->recordcnt;
			$returnArr[$r->CollectionName]['georefcnt'] = $r->georefcnt;
			$returnArr[$r->CollectionName]['dynamicProperties'] = $r->dynamicProperties;
		}
		$sql2 = 'SELECT c.CollectionName, COUNT(DISTINCT o.family) AS FamilyCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount, '.
			'COUNT(DISTINCT i.occid) AS OccurrenceImageCount '.
			'FROM omoccurrences AS o LEFT JOIN taxa AS t ON o.tidinterpreted = t.TID '.
			'INNER JOIN omcollections AS c ON o.collid = c.CollID '.
			'LEFT JOIN images AS i ON o.occid = i.occid '.
			'WHERE c.CollID IN('.$collId.') '.
			'GROUP BY c.CollectionName ';
		//echo $sql2;
		$rs = $this->conn->query($sql2);
		while($r = $rs->fetch_object()){
			$returnArr[$r->CollectionName]['familycnt'] = $r->FamilyCount;
			$returnArr[$r->CollectionName]['genuscnt'] = $r->GeneraCount;
			$returnArr[$r->CollectionName]['speciescnt'] = $r->SpeciesCount;
			$returnArr[$r->CollectionName]['TotalTaxaCount'] = $r->TotalTaxaCount;
			$returnArr[$r->CollectionName]['OccurrenceImageCount'] = $r->OccurrenceImageCount;
		}
		$sql3 = 'SELECT COUNT(DISTINCT o.family) AS FamilyCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount, '.
			'COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount, '.
            'COUNT(DISTINCT CASE WHEN i.occid IS NOT NULL THEN i.occid ELSE NULL END) AS TotalImageCount '.
			'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
			'LEFT JOIN images AS i ON o.occid = i.occid '.
			'WHERE o.collid IN('.$collId.') ';
		//echo $sql3;
		$rs = $this->conn->query($sql3);
		while($r = $rs->fetch_object()){
			$returnArr['familycnt'] = $r->FamilyCount;
			$returnArr['genuscnt'] = $r->GeneraCount;
			$returnArr['speciescnt'] = $r->SpeciesCount;
			$returnArr['TotalTaxaCount'] = $r->TotalTaxaCount;
			$returnArr['TotalImageCount'] = $r->TotalImageCount;
		}
		$rs->free();

		return $returnArr;
	}

    public function runStatisticsQuery($collId,$taxon,$country){
        $returnArr = Array();
        $pTID = '';
        $sqlFrom = 'FROM omoccurrences AS o LEFT JOIN taxa AS t ON o.tidinterpreted = t.TID '.
            'LEFT JOIN omcollections AS c ON o.collid = c.CollID ';
        $sqlWhere = 'WHERE o.collid IN('.$collId.') ';
        if($taxon){
            $sql = 'SELECT TID FROM taxa WHERE SciName = "'.$taxon.'" ';
            $rs = $this->conn->query($sql);
            while($r = $rs->fetch_object()){
                $pTID = $r->TID;
            }
            $sqlWhere .= 'AND ((o.sciname = "'.$taxon.'") OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid IN('.$pTID.')))) ';
        }
        if($country){
            $sqlWhere .= 'AND o.country = "'.$country.'" ';
        }
        $sql2 = 'SELECT c.CollID, c.CollectionName, COUNT(DISTINCT o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, '.
            'COUNT(DISTINCT o.family) AS FamilyCount, COUNT(DISTINCT t.UnitName1) AS GeneraCount, COUNT(o.typeStatus) AS TypeCount, '.
            'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS SpecimensCountID, '.
            'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount, '.
            'COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount, '.
            'COUNT(CASE WHEN ISNULL(o.family) THEN o.occid ELSE NULL END) AS SpecimensNullFamily, '.
            'COUNT(CASE WHEN ISNULL(o.country) THEN o.occid ELSE NULL END) AS SpecimensNullCountry, '.
            'COUNT(CASE WHEN ISNULL(o.decimalLatitude) THEN o.occid ELSE NULL END) AS SpecimensNullLatitude ';
        $sql2 .= $sqlFrom.$sqlWhere;
        $sql2 .= 'GROUP BY c.CollectionName ';
        //echo 'sql2: '.$sql2;
        $rs = $this->conn->query($sql2);
        while($r = $rs->fetch_object()){
            $returnArr[$r->CollectionName]['CollID'] = $r->CollID;
            $returnArr[$r->CollectionName]['CollectionName'] = $r->CollectionName;
            $returnArr[$r->CollectionName]['recordcnt'] = $r->SpecimenCount;
            $returnArr[$r->CollectionName]['georefcnt'] = $r->GeorefCount;
            $returnArr[$r->CollectionName]['speciesID'] = $r->SpecimensCountID;
            $returnArr[$r->CollectionName]['familycnt'] = $r->FamilyCount;
            $returnArr[$r->CollectionName]['genuscnt'] = $r->GeneraCount;
            $returnArr[$r->CollectionName]['speciescnt'] = $r->SpeciesCount;
            $returnArr[$r->CollectionName]['TotalTaxaCount'] = $r->TotalTaxaCount;
            $returnArr[$r->CollectionName]['types'] = $r->TypeCount;
            $returnArr[$r->CollectionName]['SpecimensNullFamily'] = $r->SpecimensNullFamily;
            $returnArr[$r->CollectionName]['SpecimensNullCountry'] = $r->SpecimensNullCountry;
            $returnArr[$r->CollectionName]['SpecimensNullLatitude'] = $r->SpecimensNullLatitude;
        }
        $sql3 = 'SELECT o.family, COUNT(o.occid) AS SpecimensPerFamily, COUNT(o.decimalLatitude) AS GeorefSpecimensPerFamily, '.
            'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerFamily, '.
            'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerFamily ';
        $sql3 .= $sqlFrom.$sqlWhere;
        $sql3 .= 'GROUP BY o.family ';
        //echo 'sql3: '.$sql3;
        $rs = $this->conn->query($sql3);
        while($r = $rs->fetch_object()){
            if($r->family){
                $returnArr['families'][$r->family]['SpecimensPerFamily'] = $r->SpecimensPerFamily;
                $returnArr['families'][$r->family]['GeorefSpecimensPerFamily'] = $r->GeorefSpecimensPerFamily;
                $returnArr['families'][$r->family]['IDSpecimensPerFamily'] = $r->IDSpecimensPerFamily;
                $returnArr['families'][$r->family]['IDGeorefSpecimensPerFamily'] = $r->IDGeorefSpecimensPerFamily;
            }
        }
        $sql4 = 'SELECT o.country, COUNT(o.occid) AS CountryCount, COUNT(o.decimalLatitude) AS GeorefSpecimensPerCountry, '.
            'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerCountry, '.
            'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerCountry ';
        $sql4 .= $sqlFrom.$sqlWhere;
        $sql4 .= 'GROUP BY o.country ';
        //echo 'sql4: '.$sql4;
        $rs = $this->conn->query($sql4);
        while($r = $rs->fetch_object()){
            if($r->country){
                $returnArr['countries'][$r->country]['CountryCount'] = $r->CountryCount;
                $returnArr['countries'][$r->country]['GeorefSpecimensPerCountry'] = $r->GeorefSpecimensPerCountry;
                $returnArr['countries'][$r->country]['IDSpecimensPerCountry'] = $r->IDSpecimensPerCountry;
                $returnArr['countries'][$r->country]['IDGeorefSpecimensPerCountry'] = $r->IDGeorefSpecimensPerCountry;
            }
        }
        $sql5 = 'SELECT c.CollID, c.CollectionName, '.
            'COUNT(DISTINCT CASE WHEN i.occid IS NOT NULL THEN i.occid ELSE NULL END) AS TotalImageCount ';
        $sql5 .= $sqlFrom;
        $sql5 .= 'LEFT JOIN images AS i ON o.occid = i.occid ';
        $sql5 .= $sqlWhere;
        $sql5 .= 'GROUP BY c.CollectionName ';
        //echo 'sql5: '.$sql5;
        $rs = $this->conn->query($sql5);
        while($r = $rs->fetch_object()){
            $returnArr[$r->CollectionName]['OccurrenceImageCount'] = $r->TotalImageCount;
        }
        $rs->free();

        return $returnArr;
    }

	public function getYearStatsHeaderArr($months){
		$dateArr = array();
		$a = $months + 1;
        $reps = $a;
		for ($i = 0; $i < $reps; $i++) {
			$timestamp = mktime(0, 0, 0, date('n') - $i, 1);
			$dateArr[$a] = date('Y', $timestamp).'-'.date('n', $timestamp);
			$a--;
		}
		ksort($dateArr);

		return $dateArr;
	}

	public function getYearStatsDataArr($collId,$days){
		$statArr = array();
		$sql = 'SELECT CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, c.collectionname '.
			'FROM omoccurrences AS o INNER JOIN omcollections AS c ON o.collid = c.collid '.
			'LEFT JOIN images AS i ON o.occid = i.occid '.
			'WHERE o.collid in('.$collId.') AND ((o.dateLastModified IS NOT NULL AND datediff(curdate(), o.dateLastModified) < '.$days.') OR (datediff(curdate(), i.InitialTimeStamp) < '.$days.')) '.
			'ORDER BY c.collectionname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$statArr[$r->collcode]['collcode'] = $r->collcode;
			$statArr[$r->collcode]['collectionname'] = $r->collectionname;
		}
		
		$sql = 'SELECT CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, CONCAT_WS("-",year(o.dateEntered),month(o.dateEntered)) as dateEntered, '.
			'c.collectionname, month(o.dateEntered) as monthEntered, year(o.dateEntered) as yearEntered, COUNT(o.occid) AS speccnt '.
			'FROM omoccurrences AS o INNER JOIN omcollections AS c ON o.collid = c.collid '.
			'WHERE o.collid in('.$collId.') AND o.dateEntered IS NOT NULL AND datediff(curdate(), o.dateEntered) < '.$days.' '.
			'GROUP BY yearEntered,monthEntered,o.collid ORDER BY c.collectionname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$statArr[$r->collcode]['stats'][$r->dateEntered]['speccnt'] = $r->speccnt;
		}
		
		$sql = 'SELECT CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, CONCAT_WS("-",year(o.dateLastModified),month(o.dateLastModified)) as dateEntered, '.
			'c.collectionname, month(o.dateLastModified) as monthEntered, year(o.dateLastModified) as yearEntered, '.
			'COUNT(CASE WHEN o.processingstatus = "unprocessed" THEN o.occid ELSE NULL END) AS unprocessedCount, '.
			'COUNT(CASE WHEN o.processingstatus = "stage 1" THEN o.occid ELSE NULL END) AS stage1Count, '.
			'COUNT(CASE WHEN o.processingstatus = "stage 2" THEN o.occid ELSE NULL END) AS stage2Count, '.
			'COUNT(CASE WHEN o.processingstatus = "stage 3" THEN o.occid ELSE NULL END) AS stage3Count '.
			'FROM omoccurrences AS o INNER JOIN omcollections AS c ON o.collid = c.collid '.
			'WHERE o.collid in('.$collId.') AND o.dateLastModified IS NOT NULL AND datediff(curdate(), o.dateLastModified) < '.$days.' '.
			'GROUP BY yearEntered,monthEntered,o.collid ORDER BY c.collectionname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$statArr[$r->collcode]['stats'][$r->dateEntered]['unprocessedCount'] = $r->unprocessedCount;
			$statArr[$r->collcode]['stats'][$r->dateEntered]['stage1Count'] = $r->stage1Count;
			$statArr[$r->collcode]['stats'][$r->dateEntered]['stage2Count'] = $r->stage2Count;
			$statArr[$r->collcode]['stats'][$r->dateEntered]['stage3Count'] = $r->stage3Count;
		}

		$sql2 = 'SELECT CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, CONCAT_WS("-",year(i.InitialTimeStamp),month(i.InitialTimeStamp)) as dateEntered, '.
			'c.collectionname, month(i.InitialTimeStamp) as monthEntered, year(i.InitialTimeStamp) as yearEntered, '.
			'COUNT(i.imgid) AS imgcnt '.
			'FROM omoccurrences AS o INNER JOIN omcollections AS c ON o.collid = c.collid '.
			'LEFT JOIN images AS i ON o.occid = i.occid '.
			'WHERE o.collid in('.$collId.') AND datediff(curdate(), i.InitialTimeStamp) < '.$days.' '.
			'GROUP BY yearEntered,monthEntered,o.collid ORDER BY c.collectionname ';
		//echo $sql2;
		$rs = $this->conn->query($sql2);
		while($r = $rs->fetch_object()){
			$statArr[$r->collcode]['stats'][$r->dateEntered]['imgcnt'] = $r->imgcnt;
		}

		$sql3 = 'SELECT CONCAT_WS("-",c.institutioncode,c.collectioncode) as collcode, CONCAT_WS("-",year(e.InitialTimeStamp),month(e.InitialTimeStamp)) as dateEntered, '.
			'c.collectionname, month(e.InitialTimeStamp) as monthEntered, year(e.InitialTimeStamp) as yearEntered, '.
			'COUNT(DISTINCT e.occid) AS georcnt '.
			'FROM omoccurrences AS o INNER JOIN omcollections AS c ON o.collid = c.collid '.
			'LEFT JOIN omoccuredits AS e ON o.occid = e.occid '.
			'WHERE o.collid in('.$collId.') AND datediff(curdate(), e.InitialTimeStamp) < '.$days.' '.
			'AND ((e.FieldName = "decimallongitude" AND e.FieldValueNew IS NOT NULL) '.
			'OR (e.FieldName = "decimallatitude" AND e.FieldValueNew IS NOT NULL)) '.
			'GROUP BY yearEntered,monthEntered,o.collid ORDER BY c.collectionname ';
		//echo $sql2;
		$rs = $this->conn->query($sql3);
		while($r = $rs->fetch_object()){
			$statArr[$r->collcode]['stats'][$r->dateEntered]['georcnt'] = $r->georcnt;
		}
		$rs->free();

		return $statArr;
	}

    public function getOrderStatsDataArr($collId){
        $statsArr = Array();
        $sql = 'SELECT (CASE WHEN t.RankId = 100 THEN t.SciName WHEN t2.RankId = 100 THEN t2.SciName ELSE NULL END) AS SciName, '.
            'COUNT(DISTINCT o.occid) AS SpecimensPerOrder, '.
            'COUNT(DISTINCT CASE WHEN o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS GeorefSpecimensPerOrder, '.
            'COUNT(DISTINCT CASE WHEN t2.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerOrder, '.
            'COUNT(DISTINCT CASE WHEN t2.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerOrder '.
            'FROM omoccurrences AS o LEFT JOIN taxaenumtree AS e ON o.tidinterpreted = e.tid '.
            'LEFT JOIN taxa AS t ON e.parenttid = t.TID '.
            'LEFT JOIN taxa AS t2 ON o.tidinterpreted = t2.TID '.
            'WHERE (o.collid IN('.$collId.')) AND (t.RankId = 100 OR t2.RankId = 100) AND e.taxauthid = 1 '.
            'GROUP BY SciName ';
        $rs = $this->conn->query($sql);
        //echo $sql;
        while($r = $rs->fetch_object()){
            $order = str_replace(array('"',"'"),"",$r->SciName);
            if($order){
                $statsArr[$order]['SpecimensPerOrder'] = $r->SpecimensPerOrder;
                $statsArr[$order]['GeorefSpecimensPerOrder'] = $r->GeorefSpecimensPerOrder;
                $statsArr[$order]['IDSpecimensPerOrder'] = $r->IDSpecimensPerOrder;
                $statsArr[$order]['IDGeorefSpecimensPerOrder'] = $r->IDGeorefSpecimensPerOrder;
            }
        }
        $rs->free();

        return $statsArr;
    }

	private function addIconImageFile(){
		$targetPath = $GLOBALS['SERVER_ROOT'].'/content/collicon/';
		$urlBase = $GLOBALS['CLIENT_ROOT'].'/content/collicon/';
		$urlPrefix = "http://";
		if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
		$urlPrefix .= $_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
		$urlBase = $urlPrefix.$urlBase;

		//Clean file name
		$fileName = basename($_FILES['iconfile']['name']);
		$imgExt = '';
		if($p = strrpos($fileName,".")) $imgExt = strtolower(substr($fileName,$p));
		$fileName = strtolower($_REQUEST["institutioncode"].($_REQUEST["collectioncode"]?'-'.$_REQUEST["collectioncode"]:''));
		$fileName = str_replace(array("%20","%23"," ","__"),"_",$fileName);
		if(strlen($fileName) > 30) $fileName = substr($fileName,0,30);
		$fileName .= $imgExt;

		//Upload file
		$fullUrl = '';
		if(move_uploaded_file($_FILES['iconfile']['tmp_name'], $targetPath.$fileName)) $fullUrl = $urlBase.$fileName;

		return $fullUrl;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	private function cleanOutArr(&$arr){
		foreach($arr as $k => $v){
			$arr[$k] = $this->cleanOutStr($v);
		}
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>