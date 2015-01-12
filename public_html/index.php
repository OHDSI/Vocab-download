<?php
$conn=oci_connect("hstan", "sld73mdj", "91.225.130.5/OMOP");
  if ( ! $conn ) {
    echo "Unable to connect: " . var_dump( oci_error() );
    die();
  }
  else {   
	$stid = oci_parse($conn, 'ALTER SESSION SET CURRENT_SCHEMA = V5DEV');
	oci_execute($stid);
	
	$stid = oci_parse($conn, 'select c.click_default, c.vocabulary_id_v4, c.vocabulary_id_v5, v.vocabulary_name, c.omop_req, c.available from vocabulary_conversion c join vocabulary v on c.vocabulary_id_v5=v.vocabulary_id');
	oci_execute($stid);
	$arVocab = array();
	while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
		if($row["OMOP_REQ"] == "Y")
			continue;
		$arVocab[] = $row;
	}
	$arVocab[] = Array (
		"CLICK_DEFAULT" => "Y",
        "VOCABULARY_ID_V4" => "",
        "VOCABULARY_ID_V5" => "",
        "VOCABULARY_NAME" => "OMOP type and metadata concepts",
        "OMOP_REQ" => "",
        "AVAILABLE" => "" 
        );
  }
?>
<?include("header.php");?>

                                                                <div class = "framed form-register">
        <em class = "corner corner-top corner-left"></em>
        <em class = "corner corner-top corner-right"></em>
        <em class = "corner corner-bottom corner-left"></em>
        <em class = "corner corner-bottom corner-right"></em>
        <h2>Restricted Vocabulary Registration and Release</h2>
        <form id="register_form" enctype="application/x-www-form-urlencoded" method="post" class="generic-form" action="/downloads.php">
<div class="input-block">
<input type="hidden" name="docname" value="Vocabulary Data CSV V4" id="docname">
</div>
<div class="input-block"><label class = "for_large" style = "" for="email">E-mail<span class="required">*</span>:</label>

<input type="text" name="email" id="email" value="" class="large" maxlength="128">
</div>
<!--<span class="hint">If registered before, enter your email and press <a href = "#" class = "a-retrieve">Retrieve</a></span> -->
<div class="input-block">
<input type="hidden" name="retrieve" value="" id="retrieve">
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
  <label class="for_large" style="" for="CDMVervion">CDM Version<span class="required">*</span>:</label>

V4 <input name="CDMVervion" id="CDMVervion" value="4" type="radio">
  V5<input name="CDMVervion" id="CDMVervion2" value="5" type="radio" checked="checked">
</div>
<!--
<div class="input-block"><label class = "for_large" style = "" for="purpose">Purpose of using the vocabulary<span class="required">*</span>:</label>

<textarea name="purpose" id="purpose" maxlength="1024" rows="6" cols="32"></textarea>
</div>
-->

<div class="input-block"><label class = "for_large" style = "" for="purpose">Select vocabularies<span class="required">*</span>:</label>

<table style="margin-left: 140px; width: 88%;" class="vocabs">
<tr style="font-weight: bold;">
<td></td>
<td width="50%">VOCABULARY NAME</td>
<td style="text-align: center;">Vocabulary ID<br/>(CDM V4)</td>
<td style="text-align: center;">Vocabulary code<br/>(CDM V5)</td>
<td style="text-align: center;">License<br/>required</td>
<td style="text-align: center;">Available</td>
</tr>
<?php foreach($arVocab as $index => $item):?>
<tr>
<td>
<input type="checkbox" name="purpose[]" value="<?=$item["VOCABULARY_ID_V4"] ? $item["VOCABULARY_ID_V4"] : "OMOPTypes"?>" id="voc<?=$item["VOCABULARY_ID_V4"] ? $item["VOCABULARY_ID_V4"] : "OMOPTypes"?>" <?php if($item["CLICK_DEFAULT"] == "Y"):?>checked="ckecked"  disabled="disabled"<?endif;?>/>
</td>
<td style="text-align: left;">
<label for="voc<?=$item["VOCABULARY_ID_V4"]?  $item["VOCABULARY_ID_V4"] : "OMOPTypes"?>" style="text-align: left;"><?=$item["VOCABULARY_NAME"]?></label>
</td>
<td style="text-align: center;"><?=$item["VOCABULARY_ID_V4"]?></td>
<td style="text-align: center;"><?=$item["VOCABULARY_ID_V5"]?></td>
<td style="text-align: center;"><?if ($item["lisence_required"]):?>Yes<?else:?> - <?endif;?></td>
<td style="text-align: center;"><?=$item["AVAILABLE"]?></td>
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
        $('#register_form .a-retrieve').click(function(e) {
            e.preventDefault();
            var form = $('#register_form');
            var email = form.find('#email').val();
            if ($.trim(email)) {
                form.find('#retrieve').val(email);
                form.submit();
            } else {
                if (!$('#email + .validation-error').size()) {
                    $('#email').after('<div class = "validation-error"></div>');
                }

                $('#email + .validation-error').text('Please enter a valid email');
            }
        });
    });
</script>
                <div class="spacer"></div>

<?include("footer.php");?>
