<?php
/*
Plugin Name: Buddypress Conditional Profile Field
Plugin URI: http://pankajanupam.com/wordpress-plugins/buddypress-conditional-profile-field
Description: Buddypress conditional profile field
Version: 2.0
Author: BigBrother
Author URI: http://pankajanupam.com

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

class xprofile_condition_profile_field {
	
	function __construct () {
		
		// add options in profile field page
		add_action('xprofile_field_additional_options',array($this,'add_extra_field_option'));
	}
	
	function add_extra_field_option ( $self ) {

		global $wpdb,$bp;
		
		// fetch profile fields
		$groups = BP_XProfile_Group::get( array(
				'fetch_fields' => true
		));
		
		// fetch object type
		$sql_query = "SELECT * FROM ".$bp->table_prefix . 'bp_xprofile_meta'." WHERE object_id='".$self->id."'";
		
		$result = $wpdb->get_row($sql_query);
		$object_type = @$result->object_type;
		
?>
		<div id="titlediv">
			<h3><label> Select Parent </label></h3>
			<select name="conditional_parent_id" id="conditional_parent_id" style="width: 30%">
			<option value="0">---</option>
<?php
			foreach ( $groups as $group ) :
			
				if($group->id != $self->group_id) continue; // exclude if not from same group
				 
				if ( ! empty( $group->fields ) ) :
			
					foreach ( $group->fields as $field ) :

						if( $field->type != 'selectbox' || $field->id == $self->id ) continue;  // exclude if not select box || exclude self					
					    echo "<option value='{$field->id}'" . ( $object_type == $field->id ? 'selected="selected"' : '' ) . ">{$field->name}</option>";

					 endforeach;
			    endif;
		   endforeach;
?>
	    </select>
	</div>
			  
		<div id="titlediv" class="hiddendiv">
		<div class="divcon">    <h3>Enter Related Option</h3>
		    <?php
		
		    $_meta_array_select =  @array_combine ( explode('|',$result->meta_key,-1),explode('|',$result->meta_value,-1) );

		    foreach ( $groups as $group ) {  
		    	if($group->id != $self->group_id) continue;
		    	
		        if ( !empty( $group->fields ) ) :
		        	
		        	foreach ( $group->fields as $field ) { 
		            	
		        		if($field->id == $self->id) continue;
		                
		        		$field = new BP_XProfile_Field($field->id);
		                $option_value = $field->get_children(); 
		
		                if($option_value == false) continue;
		                foreach($option_value as $value){
		                	$__val='';
		                	if(is_array($_meta_array_select) && !empty($_meta_array_select)){
		                                foreach($_meta_array_select as $__key=>$__val){
		                                    if($__key==$value->name) break;
		               } }
		
		echo "<div class=\"e".str_replace(' ','',$field->name)."\"> 
		        <label style=\"width:120px;display:block;float:left\">{$value->name}</label> 
		        <input style=\"width:70%\" name=\"parent[{$value->name}]\" type=\"text\" value=\"{$__val}\" /> <br /> 
			</div>";
		
	 } } // end for
		        endif;
		    }  // end for ?>
		</div></div>
		    
		<?php //TODO add script using admin_script hook?>    
		<script type="text/javascript">
		    jQuery(document).ready(function($){
		
		    	$('#saveField').live('click',function(){
		    		values = '';
		 		   jQuery('.hiddendiv input').each(function(){ 
		
		 			   values += ',' + jQuery(this).val();
		            });
		
		 		   values = values.split(',');
		
		 		   for( i=0,j=0; i < values.length; i++ ){
		     		   if ( values[i] != '' ){
		 			   		$('<input type="text" name="selectbox_option['+ ++j +']" id="selectbox_option3" value="'+values[i]+'">').appendTo("#selectbox");
		     		   }
		 		   }
		    	       
		        });

		    	allDiv = $('.hiddendiv .divcon').detach();
		        $('h3', allDiv).appendTo('.hiddendiv');
		        if($('#conditional_parent_id').val()!=0){
		                    valToShow = $('#conditional_parent_id').val();
		                    valToShow=$('#conditional_parent_id option[value="'+valToShow+'"]').text();
		                    divToAdd='.e'+$.trim(valToShow.replace(' ',''));
		                    $(divToAdd, allDiv).clone().appendTo('.hiddendiv');
		
		                    $("#selectbox").html('');
		                   // $('.e'+$.trim(valToShow)).show();
		        }
		                $('#conditional_parent_id').change(function(){
		                    $('.hiddendiv div').detach();
		                    valToHidden = $('#conditional_parent_id').val();
		                    valToHidden=$('#conditional_parent_id option[value="'+valToHidden+'"]').text();
		                    divToAdd='.e'+$.trim(valToHidden.replace(' ',''));
		                    $(divToAdd, allDiv).clone().appendTo('.hiddendiv');
		                });
		    }); 
		</script>
<?php 				
	}
	
}



add_action('xprofile_field_after_save','elance_save_exrta_filed');
function elance_save_exrta_filed($exrta_filed){	

	if( ! isset($_POST['parent']) ) return;
	
	global $wpdb,$bp;
    $meta_key = '';
    $meta_val = '';

    foreach($_POST['parent'] as $key => $val){
         
    	 $meta_key .= $key.'|';
         $meta_val .= $val.'|';
    }

	$status =   $wpdb->update($bp->table_prefix . 'bp_xprofile_meta', 
				array(
						'object_id' =>$exrta_filed->id,
						'object_type' => $_POST['conditional_parent_id'],
						'meta_key'=>$meta_key,
						'meta_value'=>$meta_val
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
	
	if(!$status){
		
		$wpdb->insert($bp->table_prefix . 'bp_xprofile_meta',
				array(
						'object_id' =>$exrta_filed->id,
						'object_type' => $_POST['conditional_parent_id'],
						'meta_key'=>$meta_key,
						'meta_value'=>$meta_val
				),
				array(
						'%d',
						'%d',
						'%s',
						'%s'
				) );
		
	}
	 	
}

new xprofile_condition_profile_field;

// add/remove drop down options
add_action('bp_after_signup_profile_fields','elace_show_hide');
add_action('bp_after_profile_field_content','elace_show_hide');

function elace_show_hide(){
    global $wpdb,$bp;

    //TODO improve sql and remove bug
    $_query = "SELECT * FROM ".$bp->table_prefix . 'bp_xprofile_fields'." a, ".$bp->table_prefix . 'bp_xprofile_meta'." b
                        WHERE a.id = b.object_type AND group_id = 1 order by b.object_id DESC";
    
    $result = $wpdb->get_results($_query);
?>
<script type="text/javascript"> 
    jQuery(document).ready(function($){
      <?php $i=0;
        foreach($result as $val){ $i++;
      ?>
               selectedVal = $('#field_<?php echo $val->object_id; ?>').val();
               allOptions<?php echo $i; ?> = $('#field_<?php echo $val->object_id; ?> option').detach();
               allOptions<?php echo $i; ?>.eq(0).appendTo('#field_<?php echo $val->object_id; ?>');        

               svalToCompare<?php echo $i; ?> = $('#field_<?php echo $val->object_type; ?>').val();
               
               $('#field_<?php echo $val->object_id; ?> option').detach();
               
        		<?php $_meta_array =  array_combine ( explode('|',$val->meta_key,-1),explode('|',$val->meta_value,-1) );
               
               
                foreach($_meta_array as $key=>$value){ ?>
                    if(svalToCompare<?php echo $i; ?> == "<?php echo $key ?>"){
                        <?php   $_value = explode(',',$value);
                                foreach($_value as $_eq_key){ ?>
                                   selectedCheck = "";
                                   if( selectedVal == "<?php echo $_eq_key ?>" ){
											selectedCheck = "selected=selected";
                                   }
                                   jQuery('<option value="<?php echo $_eq_key ?>" '+selectedCheck+' ><?php echo $_eq_key ?></option>').appendTo('#field_<?php echo $val->object_id; ?>');
                        <?php } ?>
                    }
                <?php }?>

                
                $('#field_<?php echo  $val->object_type; ?>').change(function(){
                    valToCompare<?php echo $i; ?> = $('#field_<?php echo  $val->object_type; ?>').val();
                    $('#field_<?php echo $val->object_id; ?> option').detach();
                <?php 
                
                $_meta_array =  array_combine ( explode('|',$val->meta_key,-1),explode('|',$val->meta_value,-1) );
                foreach($_meta_array as $key=>$value){ ?>
                    if(valToCompare<?php echo $i; ?> == "<?php echo $key ?>"){
                         <?php   $_value = explode(',',$value);
                                foreach($_value as $_eq_key){ ?>
                                   jQuery('<option value="<?php echo $_eq_key ?>"><?php echo $_eq_key ?></option>').appendTo('#field_<?php echo $val->object_id; ?>');
                        <?php } ?>
                    }
                <?php }?>
                });
      <?php  break; } ?>
})
</script>
<?php }  ?>