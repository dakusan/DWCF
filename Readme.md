Dakusan’s Web Communication Framework (DWCF) - v1.0 http://www.castledragmire.com/Projects/DWCF

**A communication framework between the client and server sides of a website with an emphasis on security.**

This helps facilitate quick, clean, and secure asynchronous communication for a website via AJAX and JSON.

The PHP and JS classes are independent and not required for each other. However, some of the functionality of each of the two classes complements the other.

This class can use any unicode encoding, however, you must make sure the same encoding is used on both the client and the server

##Nomenclature notes:
* _Array_ means an array with incrementing numerical keys
  * _Item_ means an item within an array
* _Object_ means an associative array that does not have just incrementing numerical keys
  * _Member_ means an item within an object
* _Parameter_ means a passed variable to a function
* <div name="RequestVariables">**_Request variables_** are received by the server in the global $_REQUEST variable</div>

[Example code is given in the last section](#PHP_SingleFileExample)

#Dependencies
* jQuery for the client module
* The server optionally uses DSQL (http://www.castledragmire.com/Projects/DSQL)

#<div name="JS_Main_Class">Client (JS) Class</div>
This is all ran through the [DWCFRequest](#JS_Main_Class) class, which is a wrapper to initiate an AJAX request through jQuery

##<div name="JS_Main_Function">DWCFRequest() function parameters</div>
1. <div name="JS_MainFunction_DataParameter">**Data** _[Required]_:</div>
  * The [request variables](#RequestVariables) to pass through to the server request
  * This is set directly to jQuery.ajax.data
  * This will also be stored in this.Data
2. <div name="JS_MainFunction_CompleteFuncParameter">**CompleteFunc** _[Required]_:</div>
  * The callback function to call on completion
  * 2 parameters are received to the callback function:
    * <div name="JS_MainFunction_CompleteFunc_DataParameter">**Data**:</div>
      * The data returned by the server (which was encoded in JSON)
      * <div name="JS_ResultMember">**Result Member**:</div>
        * The return must be an object with a “Result” member
        * If using the [DWCF PHP module](#PHP_Main_Class), the [Result](#JS_ResultMember) member will automatically be “Success”, when using [CallByAction()](#PHP_Main_CallByActionFunction), if an object is returned without this member. This helps facilitate easier error handling
      * If the below [ErrorCode](#JS_MainFunction_CompleteFunc_ErrorCodeParameter) parameter is not [ErrorCodes](#JS_Main_ErrorCodes).[Success](#JS_Main_ErrorCodes_SuccessEnum), this may contain other data (See [Error Codes](#JS_Main_ErrorCodes) section for more information)
    * <div name="JS_MainFunction_CompleteFunc_ErrorCodeParameter">**ErrorCode**</div>
      * The error code/success of the request (see [Error Codes](#JS_Main_ErrorCodes) section for more information)
  * This callback function will run in the context of the created jQuery.ajax object (this.[AjaxObj](#JS_Main_AjaxObjMember))
3. <div name="JS_MainFunction_OptionsParameter">**Options** _[Optional]_:</div>
  * An object containing overwritable options
  * The members of this object will directly be combined with this [DWCFRequest](#JS_Main_Class) object
  * The optional members:
    * <div name="JS_MainFunction_OptionName">**Name**</div>
      * Default: null
      * The name for the request (See [Request Names](#JS_Main_RequestNames))
    * <div name="JS_MainFunction_OptionURL">**URL**</div>
      * Default: The current URL without the query portion `document.location.origin+document.location.pathname`
      * The URL request calls are made to
      * You may instead want to set this to `document.location.href` to also include the query portion of the current URL
    * <div name="JS_MainFunction_OptionExecuteCompleteFuncOnError">**ExecuteCompleteFuncOnError**</div>
      * Default: true
      * Whether or not to call the [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) function if an error occurs during the request
        * The [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) function will still be called on an [ErrorCodes](#JS_Main_ErrorCodes).[ResultNotSuccessful](#JS_Main_ErrorCodes_ResultNotSuccessfulEnum) error
      * This is primarily to let the caller clean up from the action before canceling the result processing
    * <div name="JS_MainFunction_OptionExecuteCompleteFuncOnRequestIgnored">**ExecuteCompleteFuncOnRequestIgnored**</div>
      * Default: true
      * Whether or not to call the [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) function if a request is ignored due to its name already being used by another request (see [Request Names](#JS_Main_RequestNames))
      * This is primarily to let the caller clean up from the action before canceling the result processing
    * <div name="JS_MainFunction_OptionHandleError">**HandleError**</div>
      * Default: A function which informs(alerts) the user of the error
      * The function to call when an error occurs
      * This should generally only be set in [DWCFRequest](#JS_Main_Class).prototype.[HandleError](#JS_Main_DefaultMembers)
    * <div name="JS_MainFunction_OptionReturnErrorOnNonSuccessfulResult">**ReturnErrorOnNonSuccessfulResult**</div>
      * Default: true
      * If enabled, returns [ErrorCodes](#JS_Main_ErrorCodes).[ResultNotSuccessful](#JS_Main_ErrorCodes_ResultNotSuccessfulEnum) if the [Result](#JS_ResultMember) member (required) returned from the server is not “Success”
      * This is not considered a real error and will therefore not cause [HandleError()](#JS_MainFunction_OptionHandleError) to be called or [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) to not be called

##<div name="JS_Main_Members">DWCFRequest class members</div>
* Global [prototype] class members (all overwritable):
  * <div name="JS_Main_DefaultMembers">Defaults for [base request optional variables](#JS_MainFunction_OptionsParameter):</div>
    * Name, URL, ExecuteCompleteFuncOnError, ExecuteCompleteFuncOnRequestIgnored, HandleError, ReturnErrorOnNonSuccessfulResult
    * See the [Options](#JS_MainFunction_OptionsParameter) parameter of [DWCFRequest function parameters](#JS_Main_Function)
  * <div name="JS_MainMembers_UseTextProcessing">UseTextProcessing:</div>
    * If enabled, uses text (not JSONP) processing
    * This is enabled only for blackberry by default
  * DO NOT OVERWRITE THE FOLLOWING MEMBERS:
    * RequestNames, ErrorCodes, AjaxError, RawHandleError, CallCompleteFunc
* Individual class members:
  * Data
    * The object passed through the [Data](#JS_MainFunction_DataParameter) parameter of the [DWCFRequest()](#JS_Main_Function) function
    * This will receive an extra member “AJAX_SendTime” that contains a millisecond UNIX epoch timestamp
  * CompleteFunc
    * The callback function passed through the [CompleteFunc](#JS_MainFunction_CompleteFuncParameter) parameter of the [DWCFRequest()](#JS_Main_Function) function
  * <div name="JS_Main_AjaxObjMember">AjaxObj</div>
    * The jQuery.ajax object used to make the request
  * All other members specified in the [Options](#JS_MainFunction_OptionsParameter) parameter of the [DWCFRequest()](#JS_Main_Function) function

##<div name="JS_Main_AJAXCall">AJAX call information</div>
* The server call is made through jQuery.ajax
* The following options are set on the jQuery.ajax call:
  * cache
    * Value=false
    * Make sure caching does not occur
  * type
    * Value=POST
    * More compressed and reliable request variable data transfer
    * Also helps make sure caching does not occur
  * data
    * The object members from the [Data](#JS_MainFunction_DataParameter) parameter of the [DWCFRequest()](#JS_Main_Function) function, which is sent to the server as POST [request variables](#RequestVariables)
    * This will receive an extra member “AJAX_SendTime” that contains a millisecond UNIX epoch timestamp to make sure caching does not occur
  * dataType
    * If [UseTextProcessing](#JS_MainMembers_UseTextProcessing) then the value is “text”, otherwise “jsonp”
    * If [UseTextProcessing](#JS_MainMembers_UseTextProcessing), then the return from the server will be in JSON (not JSONP) and should be returned with a HTTP header Content-Type of “application/json”
    * If NOT [UseTextProcessing](#JS_MainMembers_UseTextProcessing), then the return from the server should be the JSON return data wrapped in the function provided by the “callback” request variable (in other words, JSONP) with a HTTP header Content-Type of “application/javascript”
      * However, this will only occur if the “callback” request variable is provided
  * error and complete:
    * Used internally

##<div name="JS_Main_RequestNames">Request Names</div>
* The DWCF class keeps track of currently processing requests, via names provided to the [DWCFRequest()](#JS_Main_Function) function, to make sure two requests of the same name do not run at once
* The name of a request is provided as the [Name](#JS_MainFunction_OptionName) member in the [Options](#JS_MainFunction_OptionsParameter) parameter of the [DWCFRequest()](#JS_Main_Function) function
* If the request’s name is null, this functionality will be ignored for that request

##<div name="JS_Main_ErrorCodes">Error Codes</div>
* This enum (DWCFRequest.ErrorCodes) is used for the [ErrorCode](#JS_MainFunction_CompleteFunc_ErrorCodeParameter) parameters for the [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) and [HandleError()](#JS_MainFunction_OptionHandleError) functions
* When [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) is called, its [Data](#JS_MainFunction_CompleteFunc_DataParameter) parameter is the returned object from the server unless otherwise specified in this section
* Members:
  * <div name="JS_Main_ErrorCodes_SuccessEnum">**Success**</div>
    * Value=0
    * Request was successful
    * This is guaranteed to always be 0 for conditional checking purposes
  * <div name="JS_Main_ErrorCodes_ErrorReceivingEnum">**ErrorReceiving**</div>
    * Value=1
    * Error receiving a response from the server
    * [CompleteFunc.Data](#JS_MainFunction_CompleteFunc_DataParameter)=null
  * <div name="JS_Main_ErrorCodes_InvalidResponseEnum">**InvalidResponse**</div>
    * Value=2
    * Invalid response received from the server (not JSON or JSONP)
    * [CompleteFunc.Data](#JS_MainFunction_CompleteFunc_DataParameter) contains the response text
  * <div name="JS_Main_ErrorCodes_ErrorDecodingEnum">**ErrorDecoding**</div>
    * Value=3
    * Error decoding or invalid JSON
    * [CompleteFunc.Data](#JS_MainFunction_CompleteFunc_DataParameter) is the response text when [UseTextProcessing](#JS_MainMembers_UseTextProcessing) is turned on
    * This can occur if the returned result from the server is not an object containing the [Result](#JS_ResultMember) member
  * <div name="JS_Main_ErrorCodes_ResultNotSuccessfulEnum">**ResultNotSuccessful**</div>
    * Value=4
    * If the [Result](#JS_ResultMember) member (required) returned from the server is not “Success”
    * This will only be used if [ReturnErrorOnNonSuccessfulResult](#JS_MainFunction_OptionReturnErrorOnNonSuccessfulResult) is turned on
    * This is not considered a real error and will therefore not cause [HandleError()](#JS_MainFunction_OptionHandleError) to be called or [CompleteFunc()](#JS_MainFunction_CompleteFuncParameter) to not be called
  * <div name="JS_Main_ErrorCodes_RequestIgnoredEnum">**RequestIgnored**</div>
    * Value=5
    * If the request is ignored due to its name already being used by another request (see [Request Names](#JS_Main_RequestNames))
    * [CompleteFunc.Data](#JS_MainFunction_CompleteFunc_DataParameter)=null
    * This will only be used if [ExecuteCompleteFuncOnRequestIgnored](#JS_MainFunction_OptionExecuteCompleteFuncOnRequestIgnored) is turned on

#<div name="PHP_Main_Class">Server (PHP) Class</div>
While the DWCF class greatly helps facilitate quick and easy communication via JSON, it is most importantly used for security by making sure all user input is properly sanitized and safe<br>
[Request variables](#RequestVariables) from $_REQUEST (or other such similar globals) should be retrieved through [GetVars()](#PHP_Main_GetVarsFunction) and not directly read<br>
[CallByAction()](#PHP_Main_CallByActionFunction) also helps to create physically separated action functions with guaranteed properly returned data<br>
All functions and members are currently static

##<div name="PHP_Main_Functions">Functions</div>
* <div name="PHP_Main_RetMsgFunction">**RetMsg**($DataObj)</div>
  * Outputs $DataObj encoded as JSON and optionally wrapped in a JSONP function (if the “callback” request variable is supplied), and then exits
  * “callback” must be an alphanumeric+underscore string of 1 to 100 characters (XSS precaution)
  * This is used to quickly output a return and exit without any more processing
  * If using the [DWCF Javascript module](#JS_Main_Class), $DataObj must be an object that contains a [Result](#JS_ResultMember) member
* <div name="PHP_Main_RetStrFunction">**RetStr**($StrMessage)</div>
  * Calls [RetMsg()](#PHP_Main_RetMsgFunction) with an object that only contains the [Result](#JS_ResultMember) member set to $StrMessage
  * This is mainly used to quickly output errors
* <div name="PHP_Main_CallByActionFunction">**CallByAction**()</div>
  * Calls an action processing function determined by the “Action” request variable
  * The called function is named “Action_” followed by the “Action” request variable
  * After initial script preprocessing, this function should be the only function called to process different requested actions
  * This function will end the script after processing
  * If a string/scalar [non-object] is returned from the action function, it will be output via [RetStr()](#PHP_Main_RetStrFunction)
    * This makes returning error messages simple by just returning a string from the function
  * Otherwise, outputs via [RetMsg()](#PHP_Main_RetMsgFunction) the returned (non-string) object from the called action function with the added member 'Result'=>'Success' (if [Result](#JS_ResultMember) is not already specified)
  * $_COOKIE is merged into $_REQUEST (duplicate $_REQUEST members take precedence) as cookies are not included by default on newer PHP versions
* <div name="PHP_Main_GetVarsFunction">**GetVars**($[VarArray](#PHP_Main_GetVars_VarArrayParameter), $[GetFrom](#PHP_Main_GetVars_GetFromParameter)=null)</div>
  * Pulls in, checks, and returns variables (found as members in $[GetFrom](#PHP_Main_GetVars_GetFromParameter))
  * Each requested variable is given individual constraints, and if any fail, an error will be generated for that variable
  * If all variables succeed against their constraints, an object is returned with all the requested variables (variable name’s as the member keys)
  * If any errors occur, a [GetVarsException](#PHP_Main_GetVarsException) is thrown (which contains an array with error information)
  * Parameters:
    * <div name="PHP_Main_GetVars_VarArrayParameter">**VarArray** _[Required]_</div>
      * This is an object with the variable’s names as the member’s keys, and the value as an object of constraints
      * The optional constraints are as follows:
        * **IsOptional**: If this option is not set, the checked variable must be set and not null. Otherwise, if the checked variable is not set, the returned variable is set to null
        * **RegEx**: A regular expression to match against
        * **MaxLen**: The max (unicode) string length
        * **MaxByteLen**: The max (byte) string length
        * **RegExOverrideErrorMessage**: The error message that is used on a non-matching regular expression
        * **IntRange**: The [inclusive] range a number can be in. Array(Min, Max). All given variables/constraints are run through the “floor” function for comparison
        * **FloatRange**: The [inclusive] decimal range a number can be in. Array(Min, Max)
        * **SQLLookup**:
          * A query to confirm if a value is valid. This requires the DSQL library and confirms programmatically: `if(DSQL::Query('SELECT COUNT(*) FROM '.$SQLLookup)->FetchRow(0)!=0)`
          * If SQLLookup is an array, then the first item is appended to the select part of the query, and the rest of the items are passed to the Query() function as additional parameters
        * **AutomaticValidValues**: An array of **STRING** values that, if a match occurs, make the variable considered to be valid before any other checks are processed
        * **DoNotCheckEncoding**: If given, do not confirm that the string is valid against the current unicode encoding
      * [VarArray](#PHP_Main_GetVars_VarArrayParameter) can also contain a member named “AutoVars” whose members are directly returned as variables. Variables set here can later be overwritten if checked for constraints
      * If a variable in [VarArray](#PHP_Main_GetVars_VarArrayParameter) has a value of null, the variable is considered to have no constraints
      * If a variable’s value is a boolean, it is checked against the strings “true” or “false”
    * <div name="PHP_Main_GetVars_GetFromParameter">**GetFrom** _[Optional]_</div>
      * An object with the requested variables to check (as members)
      * If null (the default), $_REQUEST is used
* <div name="PHP_Main_GetVarsRSFunction">**GetVarsRS**($[VarArray](#PHP_Main_GetVars_VarArrayParameter), $[GetFrom](#PHP_Main_GetVars_GetFromParameter)=null, $[ReturnAllErrors](#PHP_Main_GetVarsRS_ReturnAllErrorsParameter)=true)</div>
  * Wrapper for [GetVars](#PHP_Main_GetVarsFunction) which calls [RetStr](#PHP_Main_RetStrFunction) on error
  * Parameters:
    * [VarArray](#PHP_Main_GetVars_VarArrayParameter) [Required] and [GetFrom](#PHP_Main_GetVars_GetFromParameter) [Optional]
      * Directly passed to the [GetVars()](#PHP_Main_GetVarsFunction) function
    * <div name="PHP_Main_GetVarsRS_ReturnAllErrorsParameter">ReturnAllErrors _[Optional]_</div>
      * Default: true
      * If true, returns the entire [GetVarsException](#PHP_Main_GetVarsException)’s exception message
      * If false, Only returns the [ErrorString](#PHP_Main_ErrorItems_ErrorStringMember) from the first error
* <div name="PHP_Main_ErrorCodeNameFunction">**ErrorCodeName**(ErrorCode)</div>
  * Returns the member’s name from the [ErrorCodes](#JS_Main_ErrorCodes) enum that matches the given integral value
  * If the error code integer is not found, “Unknown” is returned

##<div name="PHP_Main_Members">Members</div>
* OutputCharset
  * Default: utf-8
  * This is used as the “charset” in the response HTTP header’s “Content-Type”

##<div name="PHP_Main_GetVarsException">GetVarsException Exception</div>
* This is generated by the [GetVars()](#PHP_Main_GetVarsFunction) function
* <div name="PHP_Main_GetVarsException_ErrorsMember">Contains an “Errors” member, which is an array of [ErrorItems](#PHP_Main_ErrorItems) generated by failed constraint checks of the different requested variables in the [GetVars()](#PHP_Main_GetVarsFunction) function</div>

##<div name="PHP_Main_ErrorItems">Error Items</div>
* Format:
  * Array(
    * <div name="PHP_Main_ErrorItems_VariableNameMember">VariableName:   The failed variable name</div>
    * <div name="PHP_Main_ErrorItems_ConstraintsMember">Constraints:    The passed constraints for the failed variable</div>
    * <div name="PHP_Main_ErrorItems_ErrorStringMember">ErrorString:    The generated error string</div>
    * <div name="PHP_Main_ErrorItems_ConstraintNameMember">ConstraintName: The name of the constraint that failed (ex. “IntRange”). This can also be “NotScalar”, “NotSet”, and “Encoding”</div>
  * )
* A [ConstraintName](#PHP_Main_ErrorItems_ConstraintNameMember) of “Invalid_Parameters” is set if [GetVar()](#PHP_Main_GetVarsFunction)’s [VarArray](#PHP_Main_GetVars_VarArrayParameter) or [GetFrom](#PHP_Main_GetVars_GetFromParameter) parameters do not evaluate to valid [associative] arrays. In this case, the [VariableName](#PHP_Main_ErrorItems_VariableNameMember) and [Constraints](#PHP_Main_ErrorItems_ConstraintsMember) members are empty

##<div name="PHP_Main_ExceptionMessage">Exception Message</div>
* A new-line delimited string of each item’s “[ErrorItems](#PHP_Main_ErrorItems).[ErrorString](#PHP_Main_ErrorItems_ErrorStringMember)” inside the [Errors](#PHP_Main_GetVarsException_ErrorsMember) member

#<div name="PHP_SingleFileExample">Single File Example</div>
**PHP Code:**
```php
<?
//If requested, run the action
if(isset($_REQUEST['Action']))
{
	require_once('DWCF.php');
	DWCF::CallByAction(); //Actions are executed by function name
}
function Action_TestAction() //Action=“TestAction” from the client
{
	//Extract requested variables
	extract(DWCF::GetVarsRS(Array(
		'Value'=>Array(
			'RegEx'=>'/^\d+$/', //Must match a number
			'RegExOverrideErrorMessage'=>'"Value" must be a number', //Let the user know the proper format for the variable
			'IntRange'=>Array(20, 25) //Must be between 20 and 25, or an error is automatically returned
		),
		'AppendText'=>null //No constraints (except that it is a properly encoded string)
	)));

	//This shows an example of returning an error string (this could have also been taken care of by a regular expression in GetVars)
	if($AppendText=='42')
		return 'I told you not to do that';

	return Array( //Since an array [object] is returned, this is considered successful
		'BoxNum'=>'5', //The box number on the user’s side to output the data

		//The output to show the user
		//Only AppendText needs to be escaped, as Value is already confirmed as a valid number (and converted to a number, anyways)
		'RetText'=>'Value plus 100 is '.($Value+100).'; <b>'.htmlspecialchars($AppendText, ENT_QUOTES, 'UTF-8').'</b>'
	);
}
function Action_AnotherAction() { } //Another action name (unused)

//Show the client form
header('Content-Type: text/html; charset=utf-8');
?>
```
**HTML Tie-Together Code:**
```html
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html><head>
	<title>DWCF Example</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
	<script type="text/javascript" src="DWCF.js"></script>
</head><body>

<script type="text/javascript">
```
**Javascript Code:**
```javascript
function ExecuteRequest()
{
	//Update form state (note, these are all ran before we know if the request is a duplicate or not, which is not always a good idea)
	$('span').text(''); //Clear out the text of all result spans
	$('#SendButton').attr('disabled', true); //Disable the submit button
	$('#State').text('Sending to server'); //Let the user know the request is processing

	//Send the request
	var RunQuery=new DWCFRequest({
			Action:'TestAction', //Action to run on the PHP file
			Value:$('#SendValue').val(), //“Value” variable via the text box to pass the PHP file
			AppendText:$('#SendAppendText').val() //Another example variable
		}, function(Data, ErrorCode) { //The function to run on completion
			//Update the form state
			$('#State').text('Complete'); //Let the user know the query has finished
			$('#SendButton').attr('disabled', false); //Reenable submit button
			$('#ErrorCode').text(this.ErrorCodeName(ErrorCode)); //Let the user know the success/error of the result

			//If there was an error...
			if(ErrorCode)
			{
				if(ErrorCode==this.ErrorCodes.ResultNotSuccessful) //If a not-successful error, let the user know what the error is
					$('#ErrorCode').text(function(Dummy, Val) { return Val+': '+Data.Result });
				return; //All errors handled so exit prematurely (other [catastrophic] errors handled by HandleError)
			}

			//Output the returned data string to the user in the appropriate output element
			$('#UserBox'+Data.BoxNum).html(Data.RetText); //If using .html(), make sure all data is properly escaped first!
		}, {Name:'TestQuery'}); //The name of the query is “TestQuery”. 2 queries with this name cannot be run at once
}

//Hook the form to perform the ExecuteRequest on submission
$(document).ready(function() {
	$('form').submit(function(e) {
		e.preventDefault(); //Stop form submission
		try { ExecuteRequest(); } catch(e) {} //Catch all errors to make sure this form completes successfully (so the form stops submission)
	});
});
```
**HTML Tie-Together Code:**
```html
</script>
<form action="#"> <!-- Pressing enter on text boxes also submits the form, which can demonstrate the “Request Name” functionality -->
	<table border=1 cellspacing=0 cellpadding=2>
		<tr><td>Value (20-25)</td><td><input type=text id=SendValue value=21></td></tr>
		<tr><td>Append Text</td><td><input type=text id=SendAppendText value="Do not set this to 42"></td></tr>
		<tr><td>Message</td><td><span id=UserBox5></span></td></tr>
		<tr><td>Error Code</td><td><span id=ErrorCode></span></td></tr>
		<tr><td>State</td><td><span id=State></span></td></tr>
	</table><br>
	<input type=submit value=Send id=SendButton>
</form>

</body></html>
```

Copyright and coded by Dakusan - See http://www.castledragmire.com/Copyright for more information.