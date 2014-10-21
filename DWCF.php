<?php
/***Copyright and coded by Dakusan - See http://www.castledragmire.com/Copyright for more information. ***/
/***Dakusan’s Web Communication Framework (DWCF) - v1.0 http://www.castledragmire.com/Projects/DWCF ***/

/*
While the DWCF class greatly helps facilitate quick and easy communication via JSON, it is most importantly used for security by making sure all user input is properly sanitized and safe
Request variables from $_REQUEST (or other such similar globals) should be retrieved through GetVars() and not directly read
CallByAction() also helps to create physically separated action functions with guaranteed properly returned data
All functions and members are currently static
*/

class DWCF
{
	//This is used as the “charset” in the response HTTP header’s “Content-Type”
	public static $OutputCharset='utf-8';

	//Outputs $DataObj encoded as JSON and optionally wrapped in a JSONP function (if the “callback” request variable is supplied), and then exits
	//If using the DWCF Javascript module, $DataObj must be an object that contains a “Result” member
	public static function RetMsg($DataObj)
	{
		$DataObj=json_encode($DataObj);
		if(!isset($_REQUEST['callback'])) //Return as json
			return self::OutputHTMLMessage('application/json', $DataObj);
		else if(!is_scalar($_REQUEST['callback']) || !preg_match('/^\w{1,100}$/D', $_REQUEST['callback'])) //Confirm valid callback name (XSS precaution)
			return self::OutputHTMLMessage('text/plain', 'Invalid callback function received');
		else //Return as jsonp/javascript
			return self::OutputHTMLMessage('application/javascript', "$_REQUEST[callback]($DataObj);");
	}
	private static function OutputHTMLMessage($ContentType, $Message) //Used by RetMsg
	{
		header("Content-Type: $ContentType; charset=".self::$OutputCharset);
		print $Message;
		exit;
	}

	//Calls RetMsg() with an object that only contains the “Result” member set to $StrMessage
	public static function RetStr($StrMessage) { self::RetMsg(Array("Result"=>$StrMessage)); }

	/*Calls an action processing function determined by the “Action” request variable
	The called function is named “Action_” followed by the “Action” request variable
	If a string/scalar [non-array] is returned from the action function, it will be outputted via RetMsg() as: {'Result':RETURN}
	Otherwise, outputs via RetMsg() the returned (non-string) object from the called action function with the added member 'Result'=>'Success' (if “Result” is not already specified)
	*/
	public static function CallByAction()
	{
		//Check the requested action
		if(!isset($_REQUEST['Action']) || !is_scalar($_REQUEST['Action']) || !function_exists("Action_$_REQUEST[Action]"))
			self::RetStr('Invalid Action'.(isset($_REQUEST['Action']) ? ': '.json_encode($_REQUEST['Action']) : ''));

		//Call the action function
		$_REQUEST=array_merge($_COOKIE, $_REQUEST); //Make sure cookies are in the request data since it’s turned off by default on newer PHP versions
		$FuncRet=call_user_func("Action_$_REQUEST[Action]");
		if(!is_array($FuncRet)) //If a single string (most likely an error), return it
			return self::RetStr($FuncRet);
		return self::RetMsg($FuncRet+Array('Result'=>'Success')); //Return Success by default, but it can be overridden
	}

	/*Pulls in, checks, and returns variables (found as members in $GetFrom)
	Each requested variable is given individual constraints, and if any fail, an error will be generated for that variable
	If all variables succeed against their constraints, an array is returned with all the requested variables (variable name’s as the member keys)
	If any errors occur, a GetVarsException is thrown (which contains an array with error information)
	Parameters:
		VarArray [Required]
			This is an object with the variable’s names as the member’s keys, and the value as an object of constraints
			The optional constraints are as follows:
				IsOptional: If this option is not set, the checked variable must be set and not null. Otherwise, if the checked variable is not set, the returned variable is set to null
				RegEx: A regular expression to match against
				MaxLen: The max (unicode) string length
				MaxByteLen: The max (byte) string length
				RegExOverrideErrorMessage: The error message that is used on a non-matching regular expression
				IntRange: The [inclusive] range a number can be in. Array(Min, Max). All given variables/constraints are run through the “floor” function for comparison
				FloatRange: The [inclusive] decimal range a number can be in. Array(Min, Max)
				SQLLookup:
					A query to confirm if a value is valid. This requires the DSQL library and confirms programmatically: if(DSQL::Query('SELECT COUNT(*) FROM '.$SQLLookup)->FetchRow(0)!=0)
					If SQLLookup is an array, then the first item is appended to the select part of the query, and the rest of the items are passed to the Query() function as additional parameters
				AutomaticValidValues: An array of STRING values that, if a match occurs, make the variable considered to be valid before any other constraints are processed
				DoNotCheckEncoding: If given, do not confirm that the string is valid against the current unicode encoding
			VarArray can also contain a member named “AutoVars” whose members are directly returned as variables. Variables set here can later be overwritten if checked for constraints
			If a variable in VarArray has a value of null, the variable is considered to have no constraints
			If a variable’s value is a boolean, it is checked against the strings “true” or “false”
		GetFrom [Optional]
			An object with the requested variables to check (as members)
			If null (the default), $_REQUEST is used
	*/
	public static function GetVars($VarArray, $GetFrom=null)
	{
		//Get the array to extract from
		$GetFrom=($GetFrom===null ? $_REQUEST : $GetFrom); //If null, set GetFrom to $_REQUEST
		if(!is_array($VarArray) || !is_array($GetFrom))
			throw new GetVarsException(Array(Array('VariableName'=>'', 'Constraints'=>Array(), 'ErrorString'=>'VarArray must be an array; GetFrom must be an array or null', 'ConstraintName'=>'Invalid_Parameters')));

		$Vars=isset($VarArray['AutoVars']) ? $VarArray['AutoVars'] : Array(); //Automatic variables to be returned
		unset($VarArray['AutoVars']);
		$Errors=Array();
		$ConstraintVarNames=array_fill_keys(Array('IsOptional', 'RegEx', 'MaxLen', 'MaxByteLen', 'RegExOverrideErrorMessage', 'IntRange', 'FloatRange', 'SQLLookup', 'AutomaticValidValues', 'DoNotCheckEncoding'), null);
		foreach($VarArray as $VarName => $VarCheck) //Test for each variable
		{
			//Extract variables listed in ConstraintVarNames
			$VarCheck=array_intersect_key(is_array($VarCheck) ? $VarCheck : Array(), $ConstraintVarNames); //Only remember valid constraints
			extract($VarCheck+$ConstraintVarNames); //Extract ALL constraints, setting undefined constraints to null

			//Helper function to create a GetVarException item
			$GVEI=function($ErrorString, $ConstraintName) use($VarName, $VarCheck) { return Array('VariableName'=>$VarName, 'Constraints'=>$VarCheck, 'ErrorString'=>$ErrorString, 'ConstraintName'=>$ConstraintName); };

			//If variable is not found
			if(!isset($GetFrom[$VarName]))
			{
				if(isset($IsOptional))
					$Vars[$VarName]=null;
				else
					$Errors[]=$GVEI('Variable not provided: '.$VarName, 'NotSet');
				continue;
			}

			//Confirm scalar value
			$OrigV=$V=$GetFrom[$VarName]; //OrigV is used for numeric comparisons (so may not be a string)
			if(!is_scalar($V))
			{
				$Errors[]=$GVEI('Parameter must be a scalar: '.$VarName, 'NotScalar');
				continue;
			}

			//Turn non-string scalar into a string
			if(is_bool($V))
				$V=($V ? 'true' : 'false');
			else if(!is_string($V))
				$V=(string)$V;

			//Other checks
			if(!isset($DoNotCheckEncoding) && !mb_check_encoding($V))
				$Errors[]=$GVEI('String is not encoded correctly: '.$VarName, 'Encoding');
			else if(isset($AutomaticValidValues) && in_array($V, $AutomaticValidValues, TRUE))
				$Vars[$VarName]=$OrigV;
			else if(isset($RegEx) && !preg_match($RegEx, $V)) //If variable requires a pattern, test it. If an array is given, first item is the pattern and second is the return error
				$Errors[]=$GVEI(isset($RegExOverrideErrorMessage) ? $RegExOverrideErrorMessage : 'Invalid value provided for: '.$VarName, 'RegEx');
			else if(isset($MaxLen) && mb_strlen($V)>$MaxLen)
				$Errors[]=$GVEI("$VarName is to long (>$MaxLen)", 'MaxLen');
			else if(isset($MaxByteLen) && strlen($V)>$MaxByteLen)
				$Errors[]=$GVEI("$VarName is to long (>$MaxByteLen)", 'MaxByteLen');
			else if(isset($IntRange) && (
				floor($OrigV)<($IntRange[0]=floor($IntRange[0])) || //Convert and store back to constraint
				floor($OrigV)>($IntRange[1]=floor($IntRange[1]))))
				$Errors[]=$GVEI("$VarName must be between $IntRange[0] and $IntRange[1]", 'Range');
			else if(isset($FloatRange) && (
				(float)$OrigV<($FloatRange[0]=(float)$FloatRange[0]) ||
				(float)$OrigV>($FloatRange[1]=(float)$FloatRange[1])))
				$Errors[]=$GVEI("$VarName must be between $FloatRange[0] and $FloatRange[1]", 'FloatRange');
			else if(isset($SQLLookup) && DSQL::Query(
				'SELECT COUNT(*) FROM '.(is_array($SQLLookup) ? $SQLLookup[0] : $SQLLookup), //End of query part (string itself, or first parameter of an array)
				is_array($SQLLookup) ? array_slice($SQLLookup, 1) : Array() //Parameters (if an array, everything past the first item in the array)
				)->FetchRow(0)==0)
					$Errors[]=$GVEI("$VarName cannot be found", 'SQLLookup');
			else
				$Vars[$VarName]=$OrigV;
		}
		if(count($Errors))
			throw new GetVarsException($Errors);
		return $Vars;
	}

	//Wrapper for GetVars which calls RetStr on error
	public static function GetVarsRS($VarArray, $GetFrom=null, $ReturnAllErrors=true)
	{
		try { return self::GetVars($VarArray, $GetFrom); }
		catch(GetVarsException $e) { self::RetStr($ReturnAllErrors ? $e->getMessage() : $e->Errors[0]['ErrorString']); }
	}
}

//This is generated by the GetVars() function
//Contains an “Errors” member, which is an array of “ErrorItems” generated by failed constraint checks of the different requested variables in the GetVars() function
class GetVarsException extends Exception
{
	public $Errors; //An array of ErrorItems
	public function __construct($Errors, $code=0, Exception $previous=null)
	{
		//Compile the list of error strings to create the exception message (new-line delimited string of each item’s “ErrorItems.ErrorString” inside the “Errors” member)
		$ErrorStrings=Array();
		foreach($Errors as $Error)
			$ErrorStrings[]=$Error['ErrorString'];

		//Construct the object
		$this->Errors=$Errors;
		parent::__construct(implode("\n", $ErrorStrings), $code, $previous);
	}

	/*ErrorItems:
	Format:
		Array(
			VariableName:   The failed variable name
			Constraints:    The passed constraints for the failed variable
			ErrorString:    The generated error string
			ConstraintName: The name of the constraint that failed (ex. “IntRange”). This can also be “NotScalar”, “NotSet”, and “Encoding”
		)
	A ConstraintName of “Invalid_Parameters” is set if GetVar()’s “VarArray” or “GetFrom” parameters do not evaluate to valid [associative] arrays. In this case, the “VariableName” and “Constraints” members are empty
	*/
}
?>