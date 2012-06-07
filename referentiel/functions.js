// JavaScript Document
    
 
    function validerAllCheckBox(name)
    {  
      var docInputs = new Array();
	    docInputs = document.getElementsByTagName('input');
	    for(var i=0;i<docInputs.length;i++){
		    if(docInputs[i].name == name){    
          // Check a checkbox
          if(docInputs[i].type == 'checkbox')
          {
            if ( docInputs[i].disabled == false ) 
            {
                docInputs[i].checked = true;
            }
          }
        }
      }
      return true;
    }

    function invaliderAllCheckBox(name)
    {  
      var docInputs = new Array();
	    docInputs = document.getElementsByTagName('input');
	    for(var i=0;i<docInputs.length;i++){
		    if(docInputs[i].name == name){    
          // Check a checkbox
          if(docInputs[i].type == 'checkbox')
          {
            if ( docInputs[i].disabled == false ) 
            {
                docInputs[i].checked = false;
            }
          }
        }
      }
      return true;
    }

  
    function validerCheckBox(container_id)
    {
    // Check a checkbox
        if(document.getElementById(container_id).type == 'checkbox')
        {
            checkbox =  document.getElementById(container_id);
            if ( checkbox.disabled == false ) {
                checkbox.checked = true;
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    function invaliderCheckBox(container_id)
    {
    // Uncheck a checkbox
        if(document.getElementById(container_id).type == 'checkbox')
        {
            checkbox =  document.getElementById(container_id);
            if ( checkbox.disabled == false ) {
                checkbox.checked = false;
            }
            return true;
        }
        else
        {
            return false;
        }
    }

 
 
        
