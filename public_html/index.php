<?include("include/app-config.php");?>
<?php
  $conn=oci_connect($database_user, $database_password, $database);
  if ( ! $conn ) {
    echo "Unable to connect: " . var_dump( oci_error() );
    die();
  }
  else {   
        $stid1 = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = PRODV5');
	oci_execute($stid1);
	
        $stid = oci_parse($conn, 'select c.click_default, c.vocabulary_id_v4, c.vocabulary_id_v5, v.vocabulary_name, c.omop_req, c.available, c.url, c.click_disabled from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id');
	oci_execute($stid);
	$arVocab = [];
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
            $arVocab[] = $row;
	}
  }
  // free all statement identifiers and close the database connection
oci_free_statement($stid1);
oci_free_statement($stid);
oci_close($conn);
?>
<?include("header.php");?>

<div class = "framed form-register">
        <em class = "corner corner-top corner-left"></em>
        <em class = "corner corner-top corner-right"></em>
        <em class = "corner corner-bottom corner-left"></em>
        <em class = "corner corner-bottom corner-right"></em>
        <h2>Fill out the form, pick the required vocabularies and select the right version</h2>
        <form id="register_form" enctype="application/x-www-form-urlencoded" method="post" class="generic-form" action="/downloads.php">
<div class="input-block">
<input type="hidden" name="docname" value="Vocabulary Data CSV V4" id="docname">
</div>
<div class="input-block"><label class = "for_large" style = "" for="email">E-mail<span class="required">*</span>:</label>

<input type="text" name="email" id="email" value="" class="large" maxlength="128">
</div>
<div class="input-block"><label class = "for_large" style = "" for="user_name">Your name<span class="required">*</span>:</label>

<input type="text" name="user_name" id="user_name" value="" class="large" maxlength="64">
</div>
<div class="input-block"><label class = "for_large" style = "" for="title">Title:</label>

<input type="text" name="title" id="title" value="" class="large" maxlength="255">
</div>
<div class="input-block"><label class = "for_large" style = "" for="organization">Organization<span class="required">*</span>:</label>

<input type="text" name="organization" id="organization" value="" class="large" maxlength="255">
</div>
<div class="input-block"><label class = "for_large" style = "" for="address">Address<span class="required">*</span>:</label>

<input type="text" name="address" id="address" value="" class="large" maxlength="255">
</div>
<div class="input-block"><label class = "for_large" style = "" for="city">City<span class="required">*</span>:</label>

<input type="text" name="city" id="city" value="" class="large" maxlength="64">
</div>
<div class="input-block tinyelements">
<label class = "for_large" style = "" for="country">Country<span class="required">*</span>:</label>

<input type="text" name="country" id="country" value="" class="small" maxlength="64">

<label class = "" style = "float:none" for="state">State:</label>

<input type="text" name="state" id="state" value="" class="tiny" maxlength="64">

<label class = "" style = "float:none" for="zip">Zip:</label>

<input type="text" name="zip" id="zip" value="" class="tiny" maxlength="16">
</div>
<div class="input-block"><label class = "for_large" style = "" for="phone">Phone<span class="required">*</span>:</label>

<input type="text" name="phone" id="phone" value="" class="small" maxlength="32">
</div>

<div class="input-block">
  <label class="for_large" style="" for="CDMVersion">CDM Version<span class="required">*</span>:</label>

V4.5<input name="CDMVersion" id="CDMVersion" value="4.5" type="radio">
  V5<input name="CDMVersion" id="CDMVersion2" value="5" type="radio" checked="checked">
</div>

<div class="input-block"><label class = "for_large" style = "" for="purpose">Select vocabularies<span class="required">*</span>:</label>

<table style="border-collapse:collapse; margin-left: 140px; width: 88%;" class="vocabs">
<tr style="font-weight: bold;">
<td></td>
<td style="text-align: center;">Vocabulary ID<br/>(CDM V4.5)</td>
<td style="text-align: center;">Vocabulary code<br/>(CDM V5)</td>
<!--<td style="text-align: center;">OMOP<br/>required</td>-->
<td width="50%">VOCABULARY NAME</td>
<td style="text-align: center;">Available</td>
</tr>
<?php foreach($arVocab as $index => $item):?>
<tr <?php if($item["OMOP_REQ"] == "Y"):?>style="display:none"<?endif;?>>
<td>
<input type="checkbox" name="purpose[]" value="<?=$item["VOCABULARY_ID_V4"]?>" id="voc<?=$item["VOCABULARY_ID_V4"]?>" <?php if($item["CLICK_DEFAULT"] == "Y"):?>checked="checked"<?endif;?> <?php if($item["CLICK_DISABLED"] == "Y"):?>disabled="disabled"<?endif;?> />
</td>
<td style="text-align: center;"><?=$item["VOCABULARY_ID_V4"]?></td>
<td style="text-align: center;"><?=$item["VOCABULARY_ID_V5"]?></td>
<!--<td style="text-align: center;"><?if ($item["OMOP_REQ"]):?>Yes<?else:?> - <?endif;?></td>-->
<td style="text-align: left;">
<label for="voc<?=$item["VOCABULARY_ID_V4"]?>" style="text-align: left;"><?=$item["VOCABULARY_NAME"]?></label>
</td>
<td style="text-align: center;">
    <?php if($item["AVAILABLE"] <> "Currently not available"):?><a href="<?=$item["URL"]?>"><?=$item["AVAILABLE"]?></a><?endif;?>
    <?php if($item["AVAILABLE"] == "Currently not available"):?><?=$item["AVAILABLE"]?><?endif;?></td>
</tr>

<?php endforeach;?>
</table>
</div>

<div class="submit-block">
<button name="custom" id="custom" type="submit" class="submiter">Submit</button></div>
</form>
</div>
<script>
    jQuery(function($) {
        $('#register_form').submit(function(e){
            
            var form = $('#register_form');
            
            // assume form is valid unless one of the below validation checks fails
            var form_valid = true;
            
            // email is required
            var email = form.find('#email').val();
            if ($.trim(email)) {
                // form variable is valid
                if (!$('#email + .validation-error').size()) {
                    $('#email').after('<div class = "validation-error"></div>');
                }
                $('#email + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#email + .validation-error').size()) {
                    $('#email').after('<div class = "validation-error"></div>');
                }
                $('#email + .validation-error').text('Please enter a valid email address');
                form_valid = false;
            }
            
            // name
            var user_name = form.find('#user_name').val();
            if ($.trim(user_name)) {
                // form variable is valid
                if (!$('#user_name + .validation-error').size()) {
                    $('#user_name').after('<div class = "validation-error"></div>');
                }
                $('#user_name + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#user_name + .validation-error').size()) {
                    $('#user_name').after('<div class = "validation-error"></div>');
                }
                $('#user_name + .validation-error').text('Please enter a valid name');
                form_valid = false;
            }
            
            // organization
            var organization = form.find('#organization').val();
            if ($.trim(organization)) {
                // form variable is valid
                if (!$('#organization + .validation-error').size()) {
                    $('#organization').after('<div class = "validation-error"></div>');
                }
                $('#organization + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#organization + .validation-error').size()) {
                    $('#organization').after('<div class = "validation-error"></div>');
                }
                $('#organization + .validation-error').text('Please enter a valid organization');
                form_valid = false;
            }
            
            // address
            var address = form.find('#address').val();
            if ($.trim(address)) {
                // form variable is valid
                if (!$('#address + .validation-error').size()) {
                    $('#address').after('<div class = "validation-error"></div>');
                }
                $('#address + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#address + .validation-error').size()) {
                    $('#address').after('<div class = "validation-error"></div>');
                }
                $('#address + .validation-error').text('Please enter a valid address');
                form_valid = false;
            } 
            
            // city
            var city = form.find('#city').val();
            if ($.trim(city)) {
                // form variable is valid
                if (!$('#city + .validation-error').size()) {
                    $('#city').after('<div class = "validation-error"></div>');
                }
                $('#city + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#city + .validation-error').size()) {
                    $('#city').after('<div class = "validation-error"></div>');
                }
                $('#city + .validation-error').text('Please enter a valid city');
                form_valid = false;
            } 
            
            // country
            var country = form.find('#country').val();
            if ($.trim(country)) {
                // form variable is valid
                if (!$('#country + .validation-error').size()) {
                    $('#country').after('<div class = "validation-error"></div>');
                }
                $('#country + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#country + .validation-error').size()) {
                    $('#country').after('<div class = "validation-error"></div>');
                }
                $('#country + .validation-error').text('Please enter a valid country');
                form_valid = false;
            }
            
            // phone
            var phone = form.find('#phone').val();
            if ($.trim(phone)) {
                // form variable is valid
                if (!$('#phone + .validation-error').size()) {
                    $('#phone').after('<div class = "validation-error"></div>');
                }
                $('#phone + .validation-error').text('');
            } else {
                // form variable is invalid
                if (!$('#phone + .validation-error').size()) {
                    $('#phone').after('<div class = "validation-error"></div>');
                }
                $('#phone + .validation-error').text('Please enter a valid phone number');
                form_valid = false;
            } 
            
            // allow post only if form is valid
            if (form_valid) {
                return(true);
            } else {
                return(false);
            }
            
        });
    });
</script>

<div class="spacer"></div>

<?include("footer.php");?>
