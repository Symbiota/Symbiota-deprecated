<?php
class DwcArchiverImage{

	public static function getImageArr($schemaType){
		$fieldArr['coreid'] = 'o.occid';
		$termArr['identifier'] = 'http://purl.org/dc/terms/identifier';
		$fieldArr['identifier'] = 'IFNULL(i.originalurl,i.url) as identifier';
		$termArr['accessURI'] = 'http://rs.tdwg.org/ac/terms/accessURI';
		$fieldArr['accessURI'] = 'IFNULL(i.originalurl,i.url) as accessURI';
		$termArr['thumbnailAccessURI'] = 'http://rs.tdwg.org/ac/terms/thumbnailAccessURI';	
		$fieldArr['thumbnailAccessURI'] = 'i.thumbnailurl as thumbnailAccessURI';
		$termArr['goodQualityAccessURI'] = 'http://rs.tdwg.org/ac/terms/goodQualityAccessURI';
		$fieldArr['goodQualityAccessURI'] = 'i.url as goodQualityAccessURI';
		$termArr['rights'] = 'http://purl.org/dc/terms/rights';	
		$fieldArr['rights'] = 'c.rights';
		$termArr['Owner'] = 'http://ns.adobe.com/xap/1.0/rights/Owner';	//Institution name
		$fieldArr['Owner'] = 'IFNULL(c.rightsholder,CONCAT(c.collectionname," (",CONCAT_WS("-",c.institutioncode,c.collectioncode),")")) AS owner';
		$termArr['UsageTerms'] = 'http://ns.adobe.com/xap/1.0/rights/UsageTerms';	//Creative Commons BY-SA 3.0 license
		$fieldArr['UsageTerms'] = 'i.copyright AS usageterms';
		$termArr['WebStatement'] = 'http://ns.adobe.com/xap/1.0/rights/WebStatement';	//http://creativecommons.org/licenses/by-nc-sa/3.0/us/
		$fieldArr['WebStatement'] = 'c.accessrights AS webstatement';
		$termArr['caption'] = 'http://rs.tdwg.org/ac/terms/caption';	
		$fieldArr['caption'] = 'i.caption';
		$termArr['comments'] = 'http://rs.tdwg.org/ac/terms/comments';	
		$fieldArr['comments'] = 'i.notes';
		$termArr['providerManagedID'] = 'http://rs.tdwg.org/ac/terms/providerManagedID';	//GUID
		$fieldArr['providerManagedID'] = 'g.guid AS providermanagedid';
		$termArr['MetadataDate'] = 'http://ns.adobe.com/xap/1.0/MetadataDate';	//timestamp
		$fieldArr['MetadataDate'] = 'i.initialtimestamp AS metadatadate';
		$termArr['format'] = 'http://purl.org/dc/terms/format';		//jpg
		$fieldArr['format'] = 'i.format';
		$termArr['associatedSpecimenReference'] = 'http://rs.tdwg.org/ac/terms/associatedSpecimenReference';	//reference url in portal
		$fieldArr['associatedSpecimenReference'] = '';
		$termArr['type'] = 'http://purl.org/dc/terms/type';		//StillImage
		$fieldArr['type'] = '';
		$termArr['subtype'] = 'http://rs.tdwg.org/ac/terms/subtype';		//Photograph
		$fieldArr['subtype'] = '';
		$termArr['metadataLanguage'] = 'http://rs.tdwg.org/ac/terms/metadataLanguage';	//en
		$fieldArr['metadataLanguage'] = '';

		if($schemaType == 'backup') $fieldArr['rights'] = 'i.copyright';

		$retArr['terms'] = self::trimBySchemaType($termArr, $schemaType);
		$retArr['fields'] = self::trimBySchemaType($fieldArr, $schemaType);
		return $retArr;
	}

	private static function trimBySchemaType($imageArr, $schemaType){
		$trimArr = array();
		if($schemaType == 'backup'){
			$trimArr = array('Owner', 'UsageTerms', 'WebStatement'); 
		}
		return array_diff_key($imageArr,array_flip($trimArr));
	}

	public static function getSqlImages($fieldArr, $conditionSql, $redactLocalities, $rareReaderArr){
		$sql = ''; 
		if($fieldArr && $conditionSql){
			$sqlFrag = '';
			foreach($fieldArr as $fieldName => $colName){
				if($colName) $sqlFrag .= ', '.$colName;
			}
			$sql = 'SELECT '.trim($sqlFrag,', ').
				' FROM images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'INNER JOIN guidimages g ON i.imgid = g.imgid '.
				'INNER JOIN guidoccurrences og ON o.occid = og.occid ';

			if(strpos($conditionSql,'v.clid')){
				//Search criteria came from custom search page
				$sql .= 'LEFT JOIN fmvouchers v ON o.occid = v.occid ';
			}
			if(strpos($conditionSql,'p.point')){
				//Search criteria came from map search page
				$sql .= 'LEFT JOIN omoccurpoints p ON o.occid = p.occid ';
			}
			if(strpos($conditionSql,'MATCH(f.recordedby)') || strpos($conditionSql,'MATCH(f.locality)')){
				$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
			}
			if(stripos($conditionSql,'a.stateid')){
				//Search is limited by occurrence attribute
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid ';
			}
			elseif(stripos($conditionSql,'s.traitid')){
				//Search is limited by occurrence trait
				$sql .= 'INNER JOIN tmattributes a ON o.occid = a.occid '.
					'INNER JOIN tmstates s ON a.stateid = s.stateid ';
			}
			$sql .= $conditionSql;
			if($redactLocalities){
				if($rareReaderArr){
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL OR c.collid IN('.implode(',',$rareReaderArr).')) ';
				}
				else{
					$sql .= 'AND (o.localitySecurity = 0 OR o.localitySecurity IS NULL) ';
				}
			}
		}
		//echo $sql; exit;
		return $sql;
	}
}
?>