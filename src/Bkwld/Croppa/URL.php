<?php namespace Bkwld\Croppa;

/**
 * Appends and parses params of URLs
 */
class URL {

	/**
	 * @var array
	 */
	private $config;

	/**
	 * Inject dependencies
	 *
	 * @param array $config 
	 */
	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Return the Croppa URL regex
	 *
	 * @return string
	 */
	public function pattern() {
		$pattern = '';

		// Add rest of the path up to croppa's extension
		$pattern .= '(.+)';

		// Check for the size bounds
		$pattern .= '-([0-9_]+)x([0-9_]+)';
		
		// Check for options that may have been added
		$pattern .= '(-[0-9a-zA-Z(),\-._]+)*';
		
		// Check for possible image suffixes.
		$pattern .= '\.(jpg|jpeg|png|gif|JPG|JPEG|PNG|GIF)$';

		// Return it
		return $pattern;
	}

	/**
	 * Make the regex for the route definition.  This works by wrapping both the
	 * basic Croppa pattern and the `path` config in positive regex lookaheads so
	 * they working like an AND condition.
	 * https://regex101.com/r/kO6kL1/1
	 *
	 * In the Laravel router, this gets wrapped with some extra regex before the
	 * matching happnens and for the pattern to match correctly, the final .* needs
	 * to exist.  Otherwise, the lookaheads have no length and the regex fails
	 * https://regex101.com/r/xS3nQ2/1
	 *
	 * @return string 
	 */
	public function routePattern() {
		return sprintf("(?=%s)(?=%s).+", $this->config['path'], $this->pattern());
	}

}