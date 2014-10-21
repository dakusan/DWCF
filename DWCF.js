/***Copyright and coded by Dakusan - See http://www.castledragmire.com/Copyright for more information. ***/
/***Dakusan’s Web Communication Framework (DWCF) - v1.0 http://www.castledragmire.com/Projects/DWCF ***/

//This is all ran through the “DWCFRequest” class, which is a wrapper to initiate an AJAX request through jQuery

DWCFRequest.prototype={
	//Defaults for options specified in the request function (For more information, see DWCFRequest()’s Options parameter for DWCFRequest)
	Name:null,
	URL:document.location.origin+document.location.pathname, //The current URL without the query portion
	ExecuteCompleteFuncOnError:true,
	ExecuteCompleteFuncOnRequestIgnored:true,
	HandleError:function(ErrorMessage, ErrorCode) { //By default, inform(alert) the user of the error
		alert(ErrorMessage);
	},
	ReturnErrorOnNonSuccessfulResult:true,

	/*The DWCF class keeps track of currently processing requests, via names provided to the DWCFRequest() function, to make sure two requests of the same name do not run at once
	The name of a request is provided as the “Name” member in the “Options” parameter of the DWCFRequest() function
	If the request’s name is null, this functionality will be ignored for that request
	*/
	RequestNames:{},

	//If enabled, uses text (not JSONP) processing. This is enabled only for blackberry by default
	UseTextProcessing:/blackberry/i.test(navigator.userAgent),

	/*Individual class members:
		Data: The object passed through the “Data” parameter of the DWCFRequest() function. This will receive an extra member “AJAX_SendTime” that contains a millisecond UNIX epoch timestamp
		CompleteFunc: The callback function passed through the “CompleteFunc” parameter of the DWCFRequest() function
		AjaxObj: The jQuery.ajax object used to make the request
		All other members specified in the “Options” parameter of the DWCFRequest() function
	*/
	
	//Error Codes
	//This enum is used for the “ErrorCode” parameters for the CompleteFunc() and HandleError() functions
	//When CompleteFunc() is called, its “Data” parameter is the returned object from the server unless otherwise specified in this section
	ErrorCodes:{
		Success:0,		//Request was successful. This is guaranteed to always be 0 for conditional checking purposes
		ErrorReceiving:1,	//Error receiving a response from the server (CompleteFunc.Data=null)
		InvalidResponse:2,	//Invalid response received from the server (not JSON or JSONP) (CompleteFunc.Data contains the response text)
		ErrorDecoding:3,	//Error decoding or invalid JSON (CompleteFunc.Data is the response text when this.UseTextProcessing is turned on). This can occur if the returned result from the server is not an object containing the “Result” member
		ResultNotSuccessful:4,	//If the “Result” member (required) returned from the server is not “Success”. This will only be used if ErrorCodes.ReturnErrorOnNonSuccessfulResult is turned on. This is not considered a real error and will therefore not cause HandleError() to be called or CompleteFunc() to not be called
		RequestIgnored:5	//If the request is ignored due to its name already being used by another request (see this.RequestNames) (CompleteFunc.Data=null). This will only be used if ErrorCodes.ExecuteCompleteFuncOnRequestIgnored is turned on
	},
	ErrorCodeName:function(ErrorCode) { //Returns the name of the error code
		for(var ErrorName in this.ErrorCodes)
			if(this.ErrorCodes[ErrorName]==ErrorCode)
				return ErrorName;
		return 'Unknown';
	},

	//Internal error handlers. These call HandleError
	AjaxError:function(e) {
		var ErrorReceiving=(e.readyState!=4 || e.status!=200);
		this.RawHandleError(
			ErrorReceiving ? 'Error receiving response' : 'Invalid response received: '+e.responseText, //ErrorMessage
			ErrorReceiving ? null : e.responseText, //ErrorData
			ErrorReceiving ? this.ErrorCodes.ErrorReceiving : this.ErrorCodes.InvalidResponse //ErrorCode
		)
	},
	RawHandleError:function(ErrorMessage, ErrorData, ErrorCode) //Calls HandleError and, if requested, also calls the callback function
	{
		this.HandleError(ErrorMessage, ErrorCode);
		if(this.ExecuteCompleteFuncOnError)
			this.CallCompleteFunc(ErrorData, ErrorCode);
	},

	//Call the complete function asynchronously so the ajax request can be considered complete
	CallCompleteFunc:function(Data, ErrorCode) {
		var Me=this;
		setTimeout(
			function() { Me.CompleteFunc.call(Me, Data, ErrorCode); },
			1); //Call after just 1 millisecond
	}
};

function DWCFRequest(
	/*The request variables to pass through to the server request
	This is set directly to jQuery.ajax.data
	This will also be stored in this.Data
	*/
	Data,
	/*The callback function to call on completion
	Receives 2 parameters:
		Data:
			The data returned by the server (which was encoded in JSON)
			The return must be an object with a “Result” member
			If using the DWCF PHP module, the “Result” member will automatically be “Success”, when using CallByAction(), if an object is returned without this member. This helps facilitate easier error handling
			If the below ErrorCode parameter is not ErrorCodes.Success, this may contain other data (See “Error Codes” section for more information)
		ErrorCode: The error code/success of the request (see “Error Codes” section for more information)
	This will run in the context of the created jQuery.ajax object (this.AjaxObj)
	*/
	CompleteFunc,
	/*An object containing overwritable options
	The members of this object will directly be combined with this DWCFRequest object
	The optional members:
		Name
			Default: null
			The name for the request (See this.RequestNames member)
		URL
			Default: The current URL without the query portion (document.location.origin+document.location.pathname)
			The URL request calls are made to
			You may instead want to set this to document.location.href to also include the query portion of the current URL
		ExecuteCompleteFuncOnError
			Default: true
			Whether or not to call CompleteFunc if an error occurs during the request (CompleteFunc will always be called on ErrorCodes.ResultNotSuccessful)
			This is primarily to let the caller clean up from the action before canceling the result processing
		ExecuteCompleteFuncOnRequestIgnored
			Default: true
			Whether or not to call CompleteFunc if a request is ignored due to its name already being used by another request (see this.RequestNames)
			This is primarily to let the caller clean up from the action before canceling the result processing
		HandleError
			Default: A function which informs(alerts) the user of the error
			The function to call when an error occurs
			This should generally only be set in DWCFRequest.prototype.HandleError
		ReturnErrorOnNonSuccessfulResult
			Default: true
			If enabled, returns ErrorCodes.ResultNotSuccessful if the “Result” member (required) returned from the server is not “Success”
			This is not considered a real error and will therefore not cause HandleError() to be called or CompleteFunc() to not be called
	*/
	Options
)
{
	//Confirm function was called with "new"
	if(!(this instanceof DWCFRequest))
		return alert('DWCFRequest must be initialized as a new object');

	//Merge options
	$.extend(this, Options, {Data:Data, CompleteFunc:CompleteFunc});
	Data.AJAX_SendTime=new Date().getTime(); //Add a time to the request to force ignore of cache

	//Do not allow making multiple of the same calls at once, blocked via the Name variable. Set to null to ignore this option
	if(this.Name!==null)
		if(this.RequestNames.hasOwnProperty(this.Name))
			return (this.ExecuteCompleteFuncOnRequestIgnored ? this.CallCompleteFunc(null, this.ErrorCodes.RequestIgnored) : null); //If enabled, call the complete function
		else
			this.RequestNames[this.Name]=1;

	//Make the ajax call
	var Me=this;
	this.AjaxObj=$.ajax(this.URL, $.extend({
		cache:false,	//Make sure caching does not occur
		type:'POST',	//Use POST. More compressed and reliable request variable data transfer. Also helps make sure caching does not occur
		data:Data,	//Send the passed data
		dataType:this.UseTextProcessing ? 'text' : 'jsonp', //Not using JSONP for blackberry support
		error:$.proxy(this.AjaxError, this),
		complete:function() { if(Me.Name!==null) delete Me.RequestNames[Me.Name]; }, //Mark request as completed
	}, Options)).done(function(Data) {
		//Process the data and check for errors
		if(Me.UseTextProcessing)
			try { Data=JSON.parse(Data); }
			catch(e) { return Me.RawHandleError('Invalid response received: '+Data, Data, Me.ErrorCodes.ErrorDecoding); }
		if(typeof(Data)!='object' || typeof(Data.Result)!='string')
			return Me.RawHandleError('Invalid response received: '+JSON.stringify(Data), Data, Me.ErrorCodes.ErrorDecoding);

		//Call the completion callback
		Me.CallCompleteFunc(Data, Me.ReturnErrorOnNonSuccessfulResult && Data.Result!='Success' ? Me.ErrorCodes.ResultNotSuccessful : Me.ErrorCodes.Success);
	});
}