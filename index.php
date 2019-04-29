<?php

// just call `php index.php` from the command line

require('simple_html_dom.php');
session_start(); // using sessions and other things to work around resource limitations
//unset($_SESSION['jobUrls']); session_destroy(); die();


/**
 * Scrape results from https://jobs.sanctuary-group.co.uk/search/
 * Important: Please note this is a live client site, so be wary of flooding it with requests.
 * Create a script which can scrape job results from the URL above, and all further pages of results, into a CSV file. We require the following information to be scraped as distinct values:
● Job Title
● Name of Care Home
● Location
● Department
● Operation (e.g. "Sanctuary Care")
● Requisition Number
● Salary or Hourly Rate
● Closing Date
● Description (which should exclude any information we want as a distinct value, per above)

 * The markup of these pages is not ideal for scraping, but should be structurally consistent enough.
 * Use a language of your choice for writing the scraper, and any libraries/modules/etc. you think appropriate.
 * We would anticipate this task to take between 2-4 hours in the studio, but appreciate it may not be something you've encountered before, so you may take longer as you if you need to do some research.

 * Deliverables
 * We require you commit your code and the CSV results to a git account preferrably BitBucket.
 * https://bitbucket.org/ - please send us a link to the repo when ready.
 */
 
$requestCountFailsafe = 3; // handy for resource management on my rubbish laptop :(
$requests = 0;
$requestSearchStep = 25;
$baseUrl = 'https://jobs.sanctuary-group.co.uk';

$searchUrl = $baseSearchUrl = $baseUrl.'/search/?q=&sortColumn=referencedate&sortDirection=desc&startrow=';

$csvFilename = getcwd().'/sanctuary-jobs-'.time().'.csv';

$navigate = true;

// get urls out of session
$jobUrls = !empty($_SESSION['jobUrls']) ? $_SESSION['jobUrls'] : array();	
	
// if no urls from session, get some
if(empty($jobUrls)){	
	
	log2screen(0, 'Scrape Begin');	

	while($requests < $requestCountFailsafe && $navigate != false){
		
		// failsafe
		$navigate = false;
		
		$requestUrl = $searchUrl . ($requests * $requestSearchStep);
		
		// Get Page Content
		$html = file_get_html($requestUrl);

		$requests++;
			
		log2screen(1, 'Request '.$requests, $requestUrl);	
		
		$jobs = $html->find('tr.data-row');
		
		// Scrape Jobs	
		log2screen(1, 'Scrape Jobs', count($jobs).' found');
		
		if(count($jobs) > 0){
		
			// Loop through jobs
			foreach ($jobs as $job) {
					   
				// Find item link element
				$jobTitleLink = $job->find('a.jobTitle-link', 0);
				
				// get href attribute
				$jobUrl = $baseUrl.$jobTitleLink->href;

				//log2screen(3, 'Job Link', $jobUrl);
				
				$jobUrls[] = $jobUrl;

			}

		} // end if count(jobs) > 0
		
		if(count($jobs) == $requestSearchStep){
			
			$navigate = true;
			log2screen(2, 'search again');
			
		}

	}

} else {
	
	log2screen(0, 'Urls from SESSION');
	
}

// set job urls into session
$_SESSION['jobUrls'] = $jobUrls = array_unique($jobUrls);

log2screen(0, 'Job Urls', count($jobUrls));

// Loop through Jobs

$jobs = array();

foreach($jobUrls as $i => $jobUrl){
	
	$job = array();
	
	// Get Page Content
	$html = file_get_html($jobUrl);
	
	log2screen(1, 'Job '.$i, $jobUrl);	
	
	// Scrape Jobs	
	log2screen(1, 'Scrape Page');
	
	// Job Title
	$job['title'] = !empty($html->find('span[itemprop="title"]', 0)->plaintext) ? $html->find('span[itemprop="title"]', 0)->plaintext : '';
				
	// Name of Care Home
	$job['facility'] = !empty($html->find('span[itemprop="facility"]', 0)->plaintext) ? $html->find('span[itemprop="facility"]', 0)->plaintext : '';
	
	// Location
	$job['location'] = !empty($html->find('span[itemprop="jobLocation"]', 0)->plaintext) ? $html->find('span[itemprop="jobLocation"]', 0)->plaintext : '';
	
	// Department
	$job['department'] = !empty($html->find('span[itemprop="dept"]', 0)->plaintext) ? $html->find('span[itemprop="dept"]', 0)->plaintext : '';
	
	// Operation (e.g. "Sanctuary Care")
	$job['operation'] = !empty($html->find('span[itemprop="facility"]', 0)->plaintext) ? $html->find('span[itemprop="facility"]', 0)->plaintext : '';
	
	// Requisition Number
	$job['requisition'] = !empty($html->find('span[itemprop="customfield5"]', 0)->plaintext) ? $html->find('span[itemprop="customfield5"]', 0)->plaintext : '';
	
	/** 
	 * seems like the rest of the content is in chaotic WYSIWYG format... 
	 */
	
	// Salary or Hourly Rate
	// Closing Date
	// Description (which should exclude any information we want as a distinct value, per above)
	
	$job['url'] = $jobUrl;
		
	$jobs[] = $job;
	
}

str_putcsv($jobs, $csvFilename);

// thanks https://coderwall.com/p/zvzwwa/array-to-comma-separated-string-in-php haha 
/**
 * Convert a multi-dimensional, associative array to CSV data
 * @param  array $data the array of data
 */
function str_putcsv($data, $file) {
	
	log2screen(0, 'Writing CSV', $file);
	
	# Generate CSV data from array
	$fh = fopen($file, 'w'); 
	# write out the headers
	fputcsv($fh, array_keys(current($data)));

	# write out the data
	foreach ( $data as $row ) {
		fputcsv($fh, $row);
	}

	fclose($fh);
	
}

function log2screen($inset = 0, $title, $message = ''){
	
	$_inset = '';
	
	if($inset > 0){
		
		for($i = 0; $i < $inset; $i++){
			
			$_inset = $_inset.'  ';
			
		}
		
	}
	
	echo "{$_inset}{$title}\n{$_inset}{$message}\n\n";
	
}
