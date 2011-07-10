<?php
/*
Plugin Name: GYS Themed Categories
Plugin URI: http://rumleydesign.com/plugins/gys-themes-categories-2.zip
Description: This plugin allows you to assign themes to each of your Wordpress categories - Now Wordpress 3.1.3 Compatible!!!  To assign themes to your categories, just go to Posts->Categories and you'll see a dropdown of all available themes at the bottom of the form.
Author: Mike Lopez (http://mikelopez.info/) / Luke Rumley
Version: 2.3
Author URI: http://rumleydesign.com/
*/
if(!class_exists('GYSThemedCategories')){
	class GYSThemedCategories{
		function GYSThemedCategories(){
			$this->BlogCharset=get_option('blog_charset');
			$this->OptionName=strtoupper(get_class($this));
			$this->Options=get_option($this->OptionName);
		}
		function GetOption(){
			$options=func_get_args();
			$option=$this->Options;
			foreach($options AS $o){
				$option=$option[$o];
			}
			return $option;
		}
		function SetOptions(){
			$options=func_get_args();
			for($i=0;$i<count($options);$i+=2){
				$this->Options[$options[$i]]=$options[$i+1];
			}
			update_option($this->OptionName,$this->Options);
		}

		// hooks

		// CATEGORY FORM PROCESSING
			function EditCategoryForm(){
				$themes=get_themes();
				$template=$this->GetOption('CategoryThemes',$_GET['tag_ID']);
				$options='<option value="">---</option>';
				foreach($themes AS $theme){
					$selected=$theme['Template']==$template?' selected="selected" ':'';
					$options.='<option value="'.$theme['Template'].'"'.$selected.'>'.__($theme['Name']).' '.$theme['Version'].'</option>';
				}
				$form=<<<STRING
				<div id="GYSThemedCategories">
					<h3>GYS Themed Categories</h3>
					<table class="form-table">
						<tbody>
							<tr class="form-field">
								<th valign="top" scope="row">Category Theme</th>
								<td><select name="GYSThemedCategories">{$options}</select></td>
							</tr>
						</tbody>
					</table>
				</div>
				<script type="text/javascript">
					//<![CDATA[
					function GYSThemedCategories(){
						try{
							var x=document.getElementById('GYSThemedCategories');
							var p=x.parentNode;
							var t=p.getElementsByTagName('p')[0];
							p.insertBefore(x,t);
						}catch(e){}
					}
					GYSThemedCategories();
					//]]>
				</script>
STRING;
				echo $form;
			}

			function SaveCategory($id){
				if(isset($_POST['GYSThemedCategories'])){
					$catthemes=$this->GetOption('CategoryThemes');
					if($_POST['GYSThemedCategories']){
						$catthemes[$id]=$_POST['GYSThemedCategories'];
					}else{
						unset($catthemes[$id]);
					}
					$this->SetOptions('CategoryThemes',$catthemes);
				}
			}

		// TEMPLATE PROCESSING
			function Template($template){
				$pid=$cid=0;
				$perms=get_option('permalink_structure');
				if($perms){
					// get current URL if permalinks are set
					$s=empty($_SERVER['HTTPS'])?'':$_SERVER['HTTPS']=='on'?'s':'';
					$protocol='http'.$s;
					$port=$_SERVER['SERVER_PORT']=='80'?'':':'.$_SERVER['SERVER_PORT'];
					$url=$protocol.'://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
					list($url)=explode('?',$url);
					// get Post ID from URL
					$pid=url_to_postid($url);
					// get Category ID from URL
					list($url)=explode('/page/',$url); // <- added for paging compatibility
					$cid=get_category_by_path($url,false);
					$cid=$cid->cat_ID;
				}else{
					// no permalinks so we simply check GET vars
					$pid=$_GET['p']+0;
					$cid=$_GET['cat']+0;
				}

				create_initial_taxonomies();
				if($pid){
					// we're in a post page... so let's get the first category of this post
					list($cat)=wp_get_post_categories($pid);
				}elseif($cid){
					// we're in a category page...
					$cat=$cid;
				}

				if($cat){
					// we have our category ID now so let's get the theme for it...
					$theme=$this->GetOption('CategoryThemes',$cat);
					// change template if a theme is specified for this category
					if($theme)$template=$theme;
				}

				$this->Theme=$template;
				return $template;
			}

			function Stylesheet(){
				return $this->Theme;
			}

	}
}
if(class_exists('GYSThemedCategories') && !isset($GYSThemedCategories)){
	$GYSThemedCategories=new GYSThemedCategories(__FILE__);
}

if(isset($GYSThemedCategories)){
	add_action('edit_category_form',array(&$GYSThemedCategories,'EditCategoryForm'));
	add_action('create_category',array(&$GYSThemedCategories,'SaveCategory'));
	add_action('edit_category',array(&$GYSThemedCategories,'SaveCategory'));

	add_filter('template',array(&$GYSThemedCategories,'Template'));
	add_filter('stylesheet',array(&$GYSThemedCategories,'Stylesheet'));
}
?>