<?php
/**
 * Sports Match Administrator
 *
 * Copyright © 2014-2015, Jack P. Harley, jackpharley.com
 * All Rights Reserved
 */
namespace sma\models;

use Composer\Config;
use PDO;
use sma\Database;
use sma\query\DeleteQuery;
use sma\query\InsertQuery;
use sma\query\SelectQuery;

/**
 * Download
 *
 * @package \sma\models
 */
class Download {

	public $id;

	public $type;
	const TYPE_HOMEPAGE = 1;
	const TYPE_LEAGUE_ATTACHMENT = 2;

	public $extension;

	public $title;

	public $description;

	public $leagueId;

	public $restricted;

	public static function add($file, $type, $title, $description=null, $leagueId=null, $restricted=false) {
		$db = Database::getConnection();

		$db->beginTransaction();

		$extension = pathinfo($_FILES["name"], PATHINFO_EXTENSION);

		(new InsertQuery($db))
			->into("downloads")
			->fields(["type", "extension", "title", "description", "league_id", "restricted"])
			->values("(?,?,?,?,?,?)", [$type, $extension, $title, $description, $leagueId, $restricted])
			->prepare()
			->execute();
		$id = $db->lastInsertId();

		$downloadsPath = DATA_PATH . "/downloads";
		if (!is_dir($downloadsPath))
			mkdir($downloadsPath);

		move_uploaded_file($file["tmp_name"], $downloadsPath . "/" . $id);

		$db->commit();
	}

	public static function get($id=null, $type=null, $leagueId=null, $includeRestricted=false) {
		$q = (new SelectQuery(Database::getConnection()))
			->from("downloads")
			->fields(["id", "type", "extension", "title", "description", "league_id", "restricted"]);

		if ($id)
			$q->where("id = ?", $id);
		if ($type)
			$q->where("type = ?", $type);
		if ($leagueId)
			$q->where("league_id = ?". $leagueId);
		if (!$includeRestricted)
			$q->where("restricted = 0");

		$stmt = $q->prepare();
		$stmt->execute();

		$dls = [];
		while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
			$dl = new self;
			list($dl->id, $dl->type, $dl->extension, $dl->title, $dl->description, $dl->leagueId,
				$dl->restricted) = $row;
			$dls[] = $dl;
		}

		return $dls;
	}

	public function serveDownloadViaReadfile() {
		$downloadsPath = DATA_PATH . "/downloads";

		$file = $downloadsPath . "/" . $this->id;

		$filename = self::slugify($this->title) . "." . $this->extension;
		$size = filesize($file);

		header("Content-Description: File Transfer");
		header("Content-Type: application/octet-stream");
		header("Content-Disposition: attachment; filename='" . $filename . "'");
		header("Expires: 0");
		header("Cache-Control: must-revalidate");
		header("Pragma: public");
		header("Content-Length: " . $size);
		readfile($file);
		die();
	}

	protected static function slugify($text) {
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text); // replace non letter or digits by -
		$text = trim($text, '-'); // trim
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // transliterate
		$text = preg_replace('~[^-\w]+~', '', $text); // remove unwanted characters
		if (empty($text)) {
			return 'file';
		}
		return $text;
	}
}