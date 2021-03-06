<?php $sessionstaff = $this->Session->read('staff');	?>
    <div class="contArea Clearfix">
     <div class="tabBox">
        	<?php if(isset($sessionstaff['customer_search_results'])){ ?>
      <ul>
		
      <li><a href="<?php echo $this->Html->url(array(
						    "controller" => "PatientManagement",
							"action"=>"recordpoint"
						));?>" >Record Points</a></li>
							
      <li><a href="<?php echo $this->Html->url(array(
						    "controller" => "PatientManagement",
							"action"=>"patienthistory"
						));?>">Patient History</a></li>
						
      <li><a href="<?php echo $this->Html->url(array(
						    "controller" => "PatientManagement",
							"action"=>"patientinfo"
						));?>">Patient Info</a></li>
						
     </ul>
     
     <?php } 
     ?>
     </div>
     <div class="breadcrumb_staff"><a href="<?php echo $this->Html->url(array(
						    "controller" => "ProfileFieldManagement",
							"action"=>"index"
						));?>" class="active">Profile Field Management</a> >> <b>Edit</b> </div>
     <?php echo $this->element('messagehelper'); ?>
     <div class="adminsuper">
     <form accept-charset="utf-8" method="post" id="ProfileFieldAddForm" class="admin" action="/ProfileFieldManagement/edit/<?php echo $ProfileFields['ProfileField']['id']; ?>">
      <div class="groupAdmin">
        <label><span class="star">*</span>Name</label>
        <?php $name=ucwords(str_replace('_',' ',$ProfileFields['ProfileField']['profile_field'])); ?>
<input type="text" id="ProfileFieldProfileField" maxlength="255" class="editable" placeholder="Description" required="required" name="data[ProfileField][profile_field]" value="<?php echo $name; ?>">
 <input type="hidden"  name="data[ProfileField][id]" value="<?php echo $ProfileFields['ProfileField']['id']; ?>">
 <input type="hidden"  name="data[ProfileField][clinic_id]" value="<?php echo $ProfileFields['ProfileField']['clinic_id']; ?>">
 <input type="hidden"  name="data[action]" value="update">
 </div>
       <div class="groupAdmin">
        <label><span class="star">*</span>Type</label>
<select id="ProfileFieldType" name="data[ProfileField][type]" onchange="getval();">
<!--<option value="Varchar" <?php if($ProfileFields['ProfileField']['type']=='Varchar'){ echo "selected='selected'"; } ?>>Text</option>
<option value="Text" <?php if($ProfileFields['ProfileField']['type']=='TextArea'){ echo "selected='selected'"; } ?>>MultiText</option>
<option value="Select" <?php if($ProfileFields['ProfileField']['type']=='Select'){ echo "selected='selected'"; } ?>>Select</option> -->
<option value="CheckBox" <?php if($ProfileFields['ProfileField']['type']=='CheckBox'){ echo "selected='selected'"; } ?>>CheckBox</option>
<option value="RadioButton" <?php if($ProfileFields['ProfileField']['type']=='RadioButton'){ echo "selected='selected'"; } ?>>RadioButton</option>
<option value="MultiSelect" <?php if($ProfileFields['ProfileField']['type']=='MultiSelect'){ echo "selected='selected'"; } ?>>MultiSelect</option>


</select>
 </div>
       
       <div class="groupAdmin clearfix" id="options">
           <div id="optionval" class="col-md-6 col-sm-6">
        <?php 
        $other=explode(',',$ProfileFields['ProfileField']['options']);
        $othercheck=explode('(',end($other));
        if(count($othercheck)>1){
        $ProfileFields['ProfileField']['options']=str_replace(','.end($other),'',$ProfileFields['ProfileField']['options']);    
        }
        if($ProfileFields['ProfileField']['options']!=''){
        $opt=explode(',',$ProfileFields['ProfileField']['options']);
         ?>
        
        <input type="hidden" id="cnt" name="cnt" value="<?php echo count($opt); ?>">
        <?php 
        $i=1;
        foreach($opt as $option){ ?>
        <div class="groupAdmin1" id="field<?php echo $i; ?>">
        <label>
        <?php if($i==1){ ?>
        <span class="star">*</span>Field Options
        
            <?php if($ProfileFields['ProfileField']['type']!='MultiSelect'){ ?>
                       <label class="checkbox-inline"><input type="checkbox" id="other" name="other" <?php if(count($othercheck)>1){ echo "checked";} ?>>Other</label>
            <?php } ?>
        <?php }else{ echo "&nbsp"; } ?>
        </label>
        <input type="text" id="Option<?php echo $i; ?>" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]" value="<?php echo $option; ?>">
        <?php if($i>2){ ?>
        <div onclick="removeoption(field<?php echo $i; ?>)" class="x-btn">x</div>
        <?php } if($i==1){ ?>
        <div class="add_profile icon-1" onclick="addoptionmore();">Add</div>
        <?php } ?>
        </div>
        <?php $i++;} ?>
        <?php } ?>
           </div>
              <div id="demo" class="col-md-6 col-sm-6">
                  <?php if($ProfileFields['ProfileField']['type']=='CheckBox'){  ?>
            <div class="form-group>
                <span style='display:block; font-weight:bold;'><?=$name?>:</span>
            <div>
                      <?php foreach($opt as $option){ ?>
                        <label class="checkbox-inline">
                        <input type="checkbox"><?=$option?></label>
                      <?php } if(count($othercheck)>1){ ?>
                      <label class="checkbox-inline">
                      <input type="checkbox" onclick="opt()" id="getopt">Other</label>
                      <?php } ?>
                <div class="clearfix prevhtml" id="othertext"></div>
                  </div>
            </div>
                  <?php } 
                   if($ProfileFields['ProfileField']['type']=='MultiSelect'){  ?>
                  <div class="form-group">
                      <span style="display:block; font-weight:bold;"><?=$name?>:</span>
                      <select size="4" multiple="multiple" class="form-control select-info" style="height:80px">
                          <option>Please Select</option>
                             <?php foreach($opt as $option){ ?>
                          <option><?=$option?></option>
                             <?php } ?>
                      </select></div>
                 <?php } 
                   if($ProfileFields['ProfileField']['type']=='RadioButton'){  ?>
                  <div class="form-group">
                      <span style="display:block; font-weight:bold;"><?=$name?>:</span>
                      <div class="radio_prev">
                          <?php foreach($opt as $option){ ?>
                          <div class="col-xs-6 pull-left">
                              <input type="radio" class="form-control"  name="radiobox" onclick="opt1('<?=$option?>')">
                              <label class=" control-label"><?=$option?></label>
                          </div>
                          <?php } if(count($othercheck)>1){ ?>
                  <div class="col-xs-6 pull-left">
                              <input type="radio" class="form-control" onclick="opt1('other')" name="radiobox">
                              <label class=" control-label">Other</label>
                          </div>
                   
                      <?php } ?>
                      </div></div>
                   <?php }  ?>
                <div class="clearfix prevhtml" id="othertext"></div>
       </div>
       </div>
         <div class="pull-right">
<div class="submit_pfield"><input type="button" value="Preview" class="hand-icon" onclick="getpreview();"></div>           
<div class="submit_pfield"><input type="submit" value="Save Profile Field" class="hand-icon"></div>
         </div>
     </form>
  
     </div>
     
   </div>
   </div><!-- container -->

<script>
        function opt(){

        if ($('#getopt').is(":checked"))
{
 $('#othertext').html('<input type="text" value="" placeholder="Other" class="editable1">');
}else{
     $('#othertext').html('');
    }
       
    }
    function opt1(val){

if(val=='other'){
 $('#othertext').html('<input type="text" value="" placeholder="Other" class="editable1">');
 }else{
       $('#othertext').html('');
 }
    }
    function getpreview(){
         var name=$('#ProfileFieldProfileField').val();
         var cnt=$('#cnt').val();
         for(var i=1;i<=cnt;i++){
             if($('#Option'+i).val()==''){
                 alert('Please fill all required fields for Preview')
                 return false;
             }
         }
         if(name==''){
             alert('Please fill all required fields for Preview')
             return false;
         }
         datasrc=$( "#ProfileFieldAddForm" ).serialize();
	 $.ajax({
	  type:"POST",
	  data:datasrc,
          
	  url:"<?=Staff_Name?>ProfileFieldManagement/getpreview/",
	  success:function(result){
            $("#demo").html(result);
            $("#demo").css("display", "block");
	}
  });
  
}
function getval(){
	var fieldtype=$('#ProfileFieldType').val();
	if(fieldtype=='MultiSelect'){
                   $('#optionval').html('<input type="hidden" id="cnt" name="cnt" value="2"><div class="groupAdmin1" id="field1"><label><span class="star">*</span>Field Options</label><input type="text" id="Option1" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]"><div class="add_profile icon-1" onclick="addoptionmore();">Add</div></div><div class="groupAdmin1" id="field2"><label>&nbsp;</label><input type="text" id="Option2" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]"></div>'); 
        }else{
                    $('#optionval').html('<input type="hidden" id="cnt" name="cnt" value="2"><div class="groupAdmin1" id="field1"><label><span class="star">*</span>Field Options<label class="checkbox-inline"><input type="checkbox" id="other" name="other">Other</label></label><input type="text" id="Option1" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]"><div class="add_profile icon-1" onclick="addoptionmore();">Add</div></div><div class="groupAdmin1" id="field2"><label>&nbsp;</label><input type="text" id="Option2" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]"></div>');
        }
	$("#demo").css("display", "none");	
}
function addoptionmore(){
	var cnt=$('#cnt').val();
	var inccnt=parseInt(cnt)+1;
	$( "#optionval" ).append('<div class="groupAdmin1" id="field'+inccnt+'"><label>&nbsp;</label><input type="text" id="Option'+inccnt+'" maxlength="20" class="editable1" placeholder="Option" required="required" name="data[ProfileField][options][]"><div onclick="removeoption(field'+inccnt+')" class="x-btn">x</div></div>');
	$('#cnt').val(inccnt);
}
function removeoption(id){
	var cnt=$('#cnt').val();
	var deccnt=parseInt(cnt)-1;
	$(id).remove();
	$('#cnt').val(deccnt);
}
</script>


