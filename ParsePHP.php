<?php
/**
 * PHP Code Parser
 * Currently only used to mimimize the classes of this project.
 */
class ParsePHP
{
	// Array of all parser tokens
	public $parser_tokens;

	/**
	 * Load the given PHP code or token array for parsing
	 *
	 * @param mixed $tokens an array of tokens or PHP code string
	 */
	public function __construct($tokens)
	{
		if( ! is_array($tokens))
		{
			$tokens = token_get_all($tokens);
		}

		$this->tokens = $tokens;

		// Load all parser tokens incase a child class wants them
		for ($i = 100; $i < 500; $i++)
		{
			if(($name = @token_name($i)) == 'UNKNOWN') continue;
			$this->parser_tokens[$i] = $name;
		}
	}


	/**
	 * Remove unneeded code tokens such as comments and whitespace.
	 */
	public function minimize()
	{
		$remove = array_flip(array(
			T_END_HEREDOC,
			T_PRIVATE,
			T_PUBLIC,
			T_PROTECTED,
			T_WHITESPACE,	// "\t \r\n"
			T_COMMENT,		// // or #, and /* */ in PHP 5
			T_DOC_COMMENT,	// /** Docblock
			T_BAD_CHARACTER,// anything below ASCII 32 except \t, \n and \r
			//T_OPEN_TAG	// < ?php open tag
		));

		$replace = array(
			T_PRINT => 'echo',
			T_LOGICAL_AND => '&&',
			T_LOGICAL_OR => '||',
			T_BOOL_CAST => '(bool)',
			T_INT_CAST => '(int)',
		);

		$add_space_before = array_flip(array(
			T_AS,
		));

		$add_space_after = array_flip(array(
			T_CLASS,
			T_CLONE,
			T_CONST,
			T_FINAL,
			T_FUNCTION,
			T_INSTANCEOF,
			T_NAMESPACE,
			T_NEW,
			T_STATIC,
			T_THROW,
			T_USE
		));

		$add_space = array_flip(array(
			T_EXTENDS,
			T_IMPLEMENTS,
			T_INTERFACE
		));

		$tokens = $this->tokens;

		foreach($tokens as $id => $token)
		{
			// Control characters
			if( ! is_array($token)) continue;

			list($code, $string, $line) = $token;

			// Might be able to *shrink* some stuff
			if(isset($replace[$code]))
			{
				$tokens[$id] = array($code, $replace[$code], $line);
				continue;
			}

			// Remove some stuff
			if(isset($remove[$code]))
			{
				unset($tokens[$id]);
				continue;
			}

			// "function my_function()" = T_FUNCTION then T_WHITESPACE then T_STRING
			if(isset($add_space[$code]))
			{
				$tokens[$id] = array($code, ' ' . $string . ' ', $line);
			}

			if(isset($add_space_before[$code]))
			{
				$tokens[$id] = array($code, ' ' . $string, $line);
			}

			if(isset($add_space_after[$code]))
			{
				$tokens[$id] = array($code, $string . ' ', $line);
			}

			// Look ahead for returnfunction() vs return$variables
			if($code == T_RETURN)
			{
				// Is there a function two places ahead?
				if(isset($tokens[$id + 2][0]))
				{
					$next = $tokens[$id + 2];
					if($next[0] == T_STRING)
					{
						$tokens[$id] = array($code, $string . ' ', $line);
					}
				}
			}
		}

		return $this->tokens = $tokens;
	}


	/**
	 * Convert the tokens back into a string of PHP code
	 *
	 * @return string
	 */
	public function __toString()
	{
		$output = '';

		foreach($this->tokens as $id => $token)
		{
			// Control characters
			if( ! is_array($token))
			{
				$output .= $token;
				continue;
			}

			$output .= $token[1];
		}

		return $output;
	}

}