<?php
/*
Plugin Name: Buddypress conditional profile field
Plugin URI: http://pankajanupam.in/wordpress-plugins/subdomains/
Description: Buddypress conditional profile field
Version: 0.5
Author: PANKAJ ANUPAM
Author URI: http://pankajanupam.in

* LICENSE
    Copyright 2011 PANKAJ ANUPAM  (email : mymail.anupam@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?><?php
add_action('xprofile_field_additional_options','elance_condition_profile_field');
function elance_condition_profile_field($_this){
    $_groups = BP_XProfile_Group::get( array(
        'fetch_fields' => true
    ));
    global $wpdb,$bp;
    $_query = "SELECT object_type FROM ".$bp->table_prefix . 'bp_xprofile_meta'." WHERE object_id='".$_this->id."'";
    $_result = $wpdb->get_var($_query);
?>
<div id="titlediv">
    <h3><label> Select Parent </label></h3>
    <select name="elanceparentid" id="elanceparentid" style="width: 30%">
        <option value="0">---</option>
<?php
    foreach ( $_groups as $_group ) {  if($_group->id != $_this->group_id) continue;
    if ( !empty( $_group->fields ) ) :
         foreach ( $_group->fields as $field ) {  
                if($field->type != 'selectbox')continue;
                if($field->id == $_this->id) continue;
?>                      
         <option value="<?php echo $field->id; ?>" <?php if($_result==$field->id){echo 'selected="selected"'; } ?>><?php echo $field->name; ?></option>
<?php  } // end for
    endif;
}  // end for ?>
    </select>
</div>      
<div id="titlediv" class="hiddendiv">
<div class="divcon">    <h3>Enter Related Option</h3>
    <?php
    $_query = "SELECT * FROM ".$bp->table_prefix . 'bp_xprofile_meta'." WHERE object_id='".$_this->id."'";
    $_result = $wpdb->get_row($_query);
    
    $_meta_array_select =  @array_combine ( explode('|',$_result->meta_key,-1),explode('|',$_result->meta_value,-1) );
    
        foreach ( $_groups as $_group ) {  if($_group->id != $_this->group_id) continue;
           if ( !empty( $_group->fields ) ) :
              foreach ( $_group->fields as $field ) { 
                 if($field->id == $_this->id) continue;
                    $field = new BP_XProfile_Field($field->id);
                    $option_value = $field->get_children(); 
                    if($option_value == false) continue;
                        foreach($option_value as $value){
                            if(is_array($_meta_array_select) && !empty($_meta_array_select)){
                                foreach($_meta_array_select as $__key=>$__val){
                                    if($__key==$value->name) break;
                                    else $__val='';
                                } }
?>
<div class="e<?php echo $field->name; ?>">
        <label style="width:120px;display:block;float:left"><?php echo $value->name; ?></label>
        <input name="parent[<?php echo $value->name; ?>]" type="text" value="<?php echo $__val; ?>" /> <br />
</div>

<?php } } // end for
        endif;
    }  // end for ?>
</div></div>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        allDiv = $('.hiddendiv .divcon').detach();
        $('h3', allDiv).appendTo('.hiddendiv');
        if($('#elanceparentid').val()!=0){
                    valToShow = $('#elanceparentid').val();
                    valToShow=$('#elanceparentid option[value="'+valToShow+'"]').text();
                    divToAdd='.e'+$.trim(valToShow);
                    $(divToAdd, allDiv).clone().appendTo('.hiddendiv');
                    
                   // $('.e'+$.trim(valToShow)).show();
        }
                $('#elanceparentid').change(function(){
                    $('.hiddendiv div').detach();
                    valToHidden = $('#elanceparentid').val();
                    valToHidden=$('#elanceparentid option[value="'+valToHidden+'"]').text();
                    divToAdd='.e'+$.trim(valToHidden);
                    $(divToAdd, allDiv).clone().appendTo('.hiddendiv');
                    console.log(allDiv);
                });
    })
</script>
<?php 
}
add_action('xprofile_field_after_save','elance_save_exrta_filed');
function elance_save_exrta_filed($exrta_filed){
    global $wpdb,$bp;
    $_metakey = '';
    $_metaval = '';
    foreach($_POST['parent'] as $key=>$val){
         $_metakey .=$key.'|';
         $_metaval .= $val.'|';
    }
       $_query = "SELECT id FROM ".$bp->table_prefix . 'bp_xprofile_fields'." WHERE name='".$exrta_filed->name."' and field_order='".$exrta_filed->field_order."'";
    $_result_id = $wpdb->get_var($_query);
 if($_REQUEST['mode']!='edit_field'){
    $wpdb->insert($bp->table_prefix . 'bp_xprofile_meta', 
    array( 
        'object_id' =>$_result_id,
        'object_type' => $_POST['elanceparentid'],
                'meta_key'=>$_metakey,
                'meta_value'=>$_metaval
    ), 
    array( 
        '%d', 
        '%d',
                '%s',
                '%s'
    ) );
 }else{
    $wpdb->update($bp->table_prefix . 'bp_xprofile_meta', 
    array( 
        'object_id' =>$exrta_filed->id, 
        'object_type' => $_POST['elanceparentid'],
                'meta_key'=>$_metakey,
                'meta_value'=>$_metaval
    ), 
        array( 'object_id' => $exrta_filed->id ), 
    array( 
        '%d', 
        '%d',
                '%s',
                '%s'
    ),
            array( '%d' ) 
            );
 }
}
add_action('bp_after_signup_profile_fields','elace_show_hide');
function elace_show_hide(){
    global $wpdb,$bp;
    $_query = "SELECT * FROM ".$bp->table_prefix . 'bp_xprofile_fields'." a, ".$bp->table_prefix . 'bp_xprofile_meta'." b
                        WHERE a.id = b.object_type AND group_id =1";
    
    $_result = $wpdb->get_results($_query);
?>
<script type="text/javascript"> 
    jQuery(document).ready(function($){
      <?php $i=0;
        foreach($_result as $val){ $i++;?>
               allOptions<?php echo $i; ?> = $('#field_<?php echo $val->object_id; ?> option').detach();
               allOptions<?php echo $i; ?>.eq(0).appendTo('#field_<?php echo $val->object_id; ?>');        
               svalToCompare<?php echo $i; ?> = $('#field_<?php echo $val->object_type; ?>').val();
               $('#field_<?php echo $val->object_id; ?> option').detach();
               <?php $_meta_array =  array_combine ( explode('|',$val->meta_key,-1),explode('|',$val->meta_value,-1) );
                foreach($_meta_array as $key=>$value){ ?>
                    if(svalToCompare<?php echo $i; ?> == "<?php echo $key ?>"){
                        <?php   $_value = explode(',',$value);
                                foreach($_value as $_eq_key){ ?>
                                   allOptions<?php echo $i; ?>.eq(<?php echo ((int)$_eq_key)-1; ?>).appendTo('#field_<?php echo $val->object_id; ?>');
                        <?php } ?>
                    }
                <?php }?>
                $('#field_<?php echo $val->object_type; ?>').change(function(){
                    valToCompare<?php echo $i; ?> = $('#field_<?php echo $val->object_type; ?>').val();
                    $('#field_<?php echo $val->object_id; ?> option').detach();
                <?php 
                $_meta_array =  array_combine ( explode('|',$val->meta_key,-1),explode('|',$val->meta_value,-1) );
                foreach($_meta_array as $key=>$value){ ?>
                    if(valToCompare<?php echo $i; ?> == "<?php echo $key ?>"){
                        <?php   $_value = explode(',',$value);
                                foreach($_value as $_eq_key){ ?>
                                   allOptions<?php echo $i; ?>.eq(<?php echo ((int)$_eq_key)-1; ?>).appendTo('#field_<?php echo $val->object_id; ?>');
                                     
                        <?php } ?>
                    }
                <?php }?>
                });
      <?php } ?>
})
</script>
<?php }  ?>