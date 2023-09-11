<?php

namespace App;
use \Exception;
use App\Handler\Console;
use App\Handler\ProgressBar;
use \thiagoalessio\TesseractOCR\TesseractOCR;

class Application {

	private $_outputDirectory = ROOT . DS . "output";

	public function __construct() {
		$this->console = new Console();


		if (!is_dir($this->_outputDirectory)) {
			mkdir($this->_outputDirectory, 0755);
		}
	}

	public function init($args) {
		array_shift($args);

		if (!count($args)) {
			throw new Exception("You must provide an input directory as the first argument for main.php");
			return;
		}

		$directory = preg_replace("/\/$/", "", $args[0]);
		preg_match("/[^\/]+$/", $directory, $matches);

		$isbn = isset($args[1]) ? $args[1] : readline("Please enter the ISBN for the book: ");

		$bookName = ($matches && count($matches)) ? $matches[0] : null;

		$outputDirectory = $this->_outputDirectory . DS . $bookName;
		$epubDirectory = $outputDirectory . DS . "epub";
		$pagesDirectory = $outputDirectory . DS . "pages";

		// Read directory
		$images = $this->readDirectory($directory);
		$imageCount = count($images);

		if (!!$imageCount) {
			$this->console->output("Found ${imageCount} images");
		} else {
			throw new Exception("No images found in the given directory");
		}

		$this->console->output("Creating directories");
		if (!is_dir($outputDirectory)) {
			$this->console->output($outputDirectory);
			mkdir($outputDirectory, 0755);
		}
		if (!is_dir($epubDirectory)) {
			$this->console->output($epubDirectory);
			mkdir($epubDirectory, 0755);
		}
		if (!is_dir($pagesDirectory)) {
			$this->console->output($pagesDirectory);
			mkdir($pagesDirectory, 0755);
		}

		$this->console->newline();
		$this->console->newline();

		// Scan images individually through vision API
		$progressBar = new ProgressBar([
			"steps" => $imageCount
		]);

		$output = [];
		foreach($images as $key => $image) {
			$progressBar->statusGroup($image);
			$progressBar->forward("Processing Image");

			$imagePath = $directory . DS . $image;
			$textPath = $pagesDirectory . DS . $image . ".txt";


			if (!file_exists($textPath)) {
				try {
					$progressBar->status("Running OCR");

					$text = $this->runOCR($imagePath);

					$this->saveFile($text, $textPath);

					$output[] = $text;
				} catch(Throwable $e) {
					throw new Exception($e);
				}
			} else {
				$progressBar->status("Cached file exists... skipping OCR");
				$output[] = file_get_contents($textPath);
			}
		}

		// $this->console->output("Please review the generated text");
		// readline("Press [Enter] to continue");

		$progressBar->status("Generating final text file");
		$completeText = $this->fixText(implode($output, "\n"));
		$completeTextPath = $epubDirectory . DS . "index.md";

		$this->saveFile($completeText, $completeTextPath);

		$progressBar->status("Scanning completed");
		unset($progressBar);

		$bookPath = $epubDirectory . DS . "cover.jpg";
		if (!file_exists($bookPath)) {
			$this->console->output("Generating book cover");
			$bookCover = file_get_contents("http://covers.openlibrary.org/b/isbn/$isbn-L.jpg");

			$this->saveFile($bookCover, $bookPath);
		}

		$json = $this->generateEpubJson($bookName, $isbn);

		$jsonPath = $epubDirectory . DS . "book.json";
		$this->saveFile($json, $jsonPath);

		//DONE
		echo "\007";
	}

	public function readDirectory($dir) {
		$filenames = scandir($dir);
		$output = [];

		foreach($filenames as $filename) {
			try {
				$mimeType = mime_content_type($dir . DS . $filename);

				if (strpos($mimeType, "image/") !== false) {
					$output[] = $filename;
				}
			} catch(Throwable $e) {
				throw new Exception($e->getMessage());
			}
		}

		return $output;
	}

	public function runOCR($path) {
		$ocr = new TesseractOCR($path);
		$ocr->userWords(ROOT . "dictionary.txt")
		->oem(1);

		$text = $ocr->run();

		return $this->fixText($text);
	}

	public function fixText($text) {
			// Remove page headings + page number
			$text = preg_replace("/(^[\dvxi]+\s[A-z\s]+|^[A-z\s]+\s[\dvxi]+)[\r\n]+/", "", $text);
			// Remove bottom page numbers
			$text = preg_replace("/[\r\n]{1,2}\d+$/", "", $text);
			// Add line above "Chapter X"
			$text = preg_replace("/([Cc]hapter\s\w+)/", "\n$1", $text);
			// Replace instances of 1 with I
			$text = preg_replace("/1\s/", "I ", $text);
			// Remove & anomalies
			$text = preg_replace("/&[\s\n]+(\w)/", "$1", $text);

			return $text;
	}

	public function saveFile($contents, $path) {
		$file = fopen($path, "w");
		fwrite($file, $contents);
		fclose($file);
	}

	public function generateEpubJson($title, $isbn) {
		$output = [
			"id" => $isbn,
			"title" => $title,
			"language" => "en",
			// "authors" => [
			// 	[ "name" => "John Smith", "role" => "aut"],
			// 	[ "name" => "Jane Appleseed", "role" => "dsr"]
			// ],
			// "description" => "Brief description of the book",
			// "subject" => "List of keywords, pertinent to content, separated by commas",
			// "publisher" => "ACME Publishing Inc.",
			//"rights" => "Copyright (c) 2013, Someone",
			//"date" => "2013-02-27",
			// "relation" => "http://www.acme.com/books/MyUniqueBookIDWebEdition/",

			"files" => [
				// "coverpage" => "coverpage.md",
				// "title-page" => "titlepage.md",
				"include" => [
					[ "id" => "ncx", "path" => "toc.ncx" ],
					"cover.jpg",
					// "style.css",
					"*.md",
					// "media/*"
				],
				"index" => "index.md",
				"exclude" => []
			],

			"spine" => [
				"toc" => "ncx",
				"items" => [
					// "coverpage",
					// "title-page",
					// "copyright",
					// "foreword",
					"|^c\d{1,2}-.*$|",
					"index"
				]
			]
		];

		return json_encode($output);
	}

}