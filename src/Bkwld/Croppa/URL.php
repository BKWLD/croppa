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

	/**
	 * Parse a request path into Croppa instructions
	 *
	 * @param string $request 
	 * @return array | boolean
	 */
	public function parse($request) {
		if (!preg_match('#'.$this->pattern().'#', $request, $matches)) return false;
		return [
			$this->parseRelativePath($matches[1].'.'.$matches[5]), // Path
			$matches[2] == '_' ? null : (int) $matches[2],         // Width
			$matches[3] == '_' ? null : (int) $matches[3],         // Height
			$this->parseOptions($matches[4]),                      // Options
		];
	}

	/**
	 * Take the path with Croppa options removed and get the path relative
	 * to the crops_dir 
	 *
	 * @param string $path 
	 * @return string 
	 */
	protected function parseRelativePath($path) {
		preg_match('#'.$this->config['path'].'#', $path, $matches);
		return $matches[1];
	}
	
	/**
	 * Create options array where each key is an option name
	 * and the value if an array of the passed arguments
	 * 
	 * @param  string $option_params Options string in the Croppa URL style
	 * @return array
	 */
	protected function parseOptions($option_params) {
		$options = array();
		
		// These will look like: "-quadrant(T)-resize"
		$option_params = explode('-', $option_params);
		
		// Loop through the params and make the options key value pairs
		foreach($option_params as $option) {
			if (!preg_match('#(\w+)(?:\(([\w,.]+)\))?#i', $option, $matches)) continue;
			if (isset($matches[2])) $options[$matches[1]] = explode(',', $matches[2]);
			else $options[$matches[1]] = null;
		}

		// Return new options array
		return $options;
	}

}