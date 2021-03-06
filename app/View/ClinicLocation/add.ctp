<?php

$sessionstaff = $this->Session->read('staff');	?>
<div class="contArea Clearfix">
    <div class="page-header">
        <h1>
            <i class="menu-icon fa fa-home"></i>
            Clinic Locations
            <!--
           <small>
              
           <i class="ace-icon fa fa-angle-double-right"></i>
           Draggabble Widget Boxes & Containers
           </small>
            -->
        </h1>
    </div>
     <?php 
    //echo $this->element('messagehelper'); 
    echo $this->Session->flash('good');
    echo $this->Session->flash('bad');
    ?>

      <?php echo $this->Form->create("ClinicLocation",array('class'=>'form-horizontal'));
       ?>
    <div class="col-sm-7">
        <input type="hidden" id="clinic_id" name="clinic_id" value="<?=$sessionstaff['clinic_id']?>">
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>Address:</label>

            <div class="col-sm-9">
                <textarea name="address" id="address" rows="6" cols="30" placeholder="Address" class="col-xs-10 col-sm-12"></textarea>

            </div>
        </div>
        <div class="form-group">
                <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>Email:</label>
                <div class="col-sm-9">
                    <input type="text"  maxlength="50" class="col-xs-10 col-sm-12" placeholder="Email" id="email" name="email" value="">
                </div>
            </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>State:</label>

            <div class="col-sm-9">
                <select class="col-xs-10 col-sm-12" name="state" id="state" onchange="getcity();">
                    <option value="">Select State</option>
	<?php foreach($states as $st){ ?>
                    <option value="<?=$st['State']['state']?>"><?=$st['State']['state']?></option>
<?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>City:</label>

            <div class="col-sm-9">
                <select class="col-xs-10 col-sm-12" name="city" id="city">
                    <option value="">Select City</option>

                </select>
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>Zipcode:</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="100" class="col-xs-10 col-sm-12" placeholder="Zipcode" id="pincode" name="pincode" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1"><span class="star">*</span>Phone Number:</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="100" class="col-xs-10 col-sm-12" placeholder="Phone Number" id="phone" name="phone" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1">Fax Number:</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="100" class="col-xs-10 col-sm-12" placeholder="Fax Number" id="fax" name="fax" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1">Google Business Page Url  :</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="255" class="col-xs-10 col-sm-12" placeholder="Google Business Page Url" id="google_business_page_url" name="google_business_page_url" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1">Yahoo Business Page Url  :</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="255" class="col-xs-10 col-sm-12" placeholder="Yahoo Business Page Url" id="yahoo_business_page_url" name="yahoo_business_page_url" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1">Yelp Business Page Url  :</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="255" class="col-xs-10 col-sm-12" placeholder="Yelp Business Page Url" id="yelp_business_page_url" name="yelp_business_page_url" value="">
            </div>
        </div>
        <div class="form-group">
            <label  class="col-sm-3 control-label no-padding-right" for="form-field-1">Healthgrades Business Page Url  :</label>

            <div class="col-sm-9">
                <input type="text"  maxlength="255" class="col-xs-10 col-sm-12" placeholder="Healthgrades Business Page Url" id="healthgrades_business_page_url" name="healthgrades_business_page_url" value="">
            </div>
        </div>
    </div>
    <div class="col-sm-3"></div>

    <div class="col-sm-8 col-md-offset-3 col-md-9 submitBtn-Box">

        <input type="submit" value="Save Location" class="btn btn-info" >
    </div> 
</form>


</div>




<script language="Javascript">


    $(document).ready(function() {


$.validator.addMethod("zipRegex", function(value, element) {
        return this.optional(element) || /^[a-z0-9]+$/i.test(value);
    }, "Zipcode must contain only alphanumeric.");

        $('#ClinicLocationAddForm').validate({
            errorElement: 'div',
            errorClass: 'help-block',
            focusInvalid: false,
            rules: {
                address: "required",
                email: {
                    required: true, email: true
                },
                state: "required",
                city: "required",
                pincode: {
                    required: true,
                    zipRegex:true,
                                 minlength: 4 ,maxlength:6
                },
                phone: {
                    required: true,
                    number: true,
                    minlength: 7, maxlength: 10
                },
               
            },
            // Specify the validation error messages
            messages: {
                address: "Please enter address",
                email: {
                    required: "Please enter email address",
                    email: "Please enter a valid email address",
                },
                state: "Please select state",
                city: "Please select city",
                pincode: {
                    required: "Please enter Zipcode",
                    zipRegex:"Please enter valid zipcode",
                    minlength: "Zipcode must be 4 to 6 characters long"
                },
                phone: {
                    required: "Please enter phone number",
                    number: "Please enter valid phone number",
                    minlength: "Phone Number must be 7 to 10 characters long"
                },
                
            },
            highlight: function(e) {
                $(e).closest('.form-group').removeClass('has-info').addClass('has-error');
            },
            success: function(e) {
                $(e).closest('.form-group').removeClass('has-error');//.addClass('has-info');
                $(e).remove();
            },
            showErrors: function(errorMap, errorList) {
                if (errorList.length) {
                    var s = errorList.shift();
                    var n = [];
                    n.push(s);
                    this.errorList = n;
                }
                this.defaultShowErrors();
            },
            submitHandler: function(form) {
                $.ajax({
                    type: "POST",
                    data: "zip=" + $('#pincode').val() + "&state=" + $('#state').val(),
                    url: "<?=Staff_Name?>ClinicLocation/checkzip/",
                    success: function(result) {
                        if (result == 1) {
                            var fax = $('#fax').val();
                            var re = /^[ 0-9_@./#&()+-]*$/
                            if (re.test(fax)) {
                                form.submit();
                            }
                            else {
                                alert('Please Enter Valid Fax');
                                return false;
                            }
                            
                        } else {
                            $("#pincode").focus();
                            alert('Pincode entered does not match the state');
                            return false;
                        }

                    }});
            }

        });
    });
    function getcity() {

        var state = $('#state').val();

        $.ajax({
            type: "POST",
            data: "state=" + state,
            url: "<?=Staff_Name?>PatientManagement/getcity/",
            success: function(result) {
                $('#city').html(result);
            }});




    }
</script>

