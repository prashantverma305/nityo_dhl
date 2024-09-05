<?php
/**
 * @file
 * Contains \Drupal\dhl_location\Form\DHLForm.
 */
declare(strict_types=1);

namespace Drupal\dhl_location\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Locale\CountryManager;
use Symfony\Component\Yaml\Yaml;

class DHLForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dhl_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
	
	$country_manager = \Drupal::service('country_manager');
    $countries = $country_manager->getList();
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a country'),
      '#options' => $countries,
      '#default_value' => '',
	  '#attributes' => ['class' => ['form-control','form-style']],
	  '#prefix' => '<div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3">',
	  '#suffix' => '</div>',
      '#required' => TRUE,
    ];
    $form['city'] = array(
      '#type' => 'textfield',
      '#title' => t('city'),
	  '#attributes' => ['class' => ['form-control','form-style']],
      '#prefix' => '<div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3">',
      '#suffix' => '</div>',
      '#required' => TRUE,
    );
	$form['p_code'] = array(
		'#type' => 'textfield',
		'#title' => t('Postal Code'),
		'#attributes' => ['class' => ['form-control','form-style']],
        '#prefix' => '<div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3">',
        '#suffix' => '</div>',
		'#required' => TRUE,
	  );
    $form['actions'] = [
      '#type' => 'button',
      '#value' => $this->t('Submit'),
	  '#attributes' => ['class' => ['form-control','form-style']],
      '#prefix' => '<div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => '::setMessage',
      ],
    ];
	$form['#theme'] = 'dhl_check_location';
	
    return $form;
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::messenger()->addMessage(t("Student Registration Done!! Registered Values are:"));
	foreach ($form_state->getValues() as $key => $value) {
	  \Drupal::messenger()->addMessage($key . ': ' . $value);
    }
  }
 /**
 *
 */
  public function setMessage(array $form, FormStateInterface $form_state) {
	
	global $base_url;
	
	if(!empty($form_state->getValue('country'))){
	$country  = $form_state->getValue('country');
	$city  = (!empty($form_state->getValue('city')) && !empty($form_state->getValue('city')))?$form_state->getValue('city'):"";
	$p_code  = (!empty($form_state->getValue('p_code')) && !empty($form_state->getValue('p_code')))?$form_state->getValue('p_code'):"";
	$city = htmlspecialchars(str_replace(' ', '', $city));
	$postdata = array('country'=>$country, 'city'=>$city, 'p_code'=>$p_code);
	
	$curldata  = json_decode($this->getCurlData($postdata));
	}
	if(isset($curldata)){
	$output .='<div class="field field-name-body field-type-text-with-summary field-label-hidden">
	<div class="field-items">
		<div class="field-item even" property="content:encoded">
			
			<table class="table table-bordered table-striped">
			<thead>
				<tr>
					<th>Sr No.</th>
					<th>Location </th>
					<th>Name </th>
					<th>Address</th>	
					<th>Distance</th>
				</tr>
			</thead>
			<tbody>';
			$i=1;
			if(!empty($curldata)){
				
				
				foreach($curldata->locations as $key =>  $d){
					$locations_data[$key] = [];
					$locations = [];
					$alldays[$key] = [];			
					foreach($d->openingHours as $days){
						array_push($alldays[$key],$days);
					}	
					$d->alldays[$key] = count($alldays[$key]);
					$numberisodd = $this->check_odd_no($d->location->ids['0']->locationId);
					if($d->alldays[$key] == 7 && $numberisodd != 'Odd'){
					
					$locations['locationName'] = $d->location->ids['0']->locationId.' - '.$d->location->ids['0']->provider;	
					$locations['address']['countryCode']  = $d->place->address->countryCode;
					$locations['address']['postalCode']  = $d->place->address->postalCode;
					$locations['address']['addressLocality']  = $d->place->address->addressLocality;
					$locations['address']['streetAddress']  = $d->place->address->streetAddress;
					$locations['openingHours']['monday']  = $d->openingHours['0']->opens.'-'.$d->openingHours['0']->closes;
					$locations['openingHours']['tuesday']  = $d->openingHours['1']->opens.'-'.$d->openingHours['1']->closes;
					$locations['openingHours']['wednesday']  = $d->openingHours['2']->opens.'-'.$d->openingHours['2']->closes;
					$locations['openingHours']['thursday']  = $d->openingHours['3']->opens.'-'.$d->openingHours['3']->closes;
					$locations['openingHours']['friday']  = $d->openingHours['4']->opens.'-'.$d->openingHours['4']->closes;
					$locations['openingHours']['Saturday']  = $d->openingHours['5']->opens.'-'.$d->openingHours['5']->closes;
					$locations['openingHours']['sunday']  = $d->openingHours['6']->opens.'-'.$d->openingHours['6']->closes;

					//table
					$output .='<tr><td>'.$i.'</td>';	
					$output .='<td>'.$d->location->ids['0']->locationId.' - '.$d->location->ids['0']->provider. '</td>';					
					$output .='<td>'.$d->name.'</td>';
					$output .='<td>'.$d->place->address->countryCode.'-'.$d->place->address->postalCode.'-'.$d->place->address->addressLocality.'-'.$d->place->address->streetAddress.'</td>';
					$output .='<td>'.$d->distance.'</td>';
					$yaml_data .= Yaml::dump($locations, 2, 4);
					$yaml = '<pre>' . htmlspecialchars($yaml_data) . '</pre>';
				}
				
				
				$output .= '</tr>';
			 $i++;
			} 
			}else{ 
				$output .='<tr><td colspan="7">No data Found!</td></tr>';	
			 }
			$output .='</tbody></table></div></div></div>';
	}

    $response = new AjaxResponse();
    $response->addCommand( new HtmlCommand('.result-message',$output),);
	$response->addCommand( new HtmlCommand('.result-message-yaml',$yaml));
	
    return $response;

   }
function getCurlData($postdata){
	$curl = curl_init();

	curl_setopt_array($curl, [
		// CURLOPT_URL => "https://api.dhl.com/location-finder/v1/find-by-address?countryCode=DE&addressLocality=Dresden&postalCode:=01067",
		CURLOPT_URL => "https://api.dhl.com/location-finder/v1/find-by-address?countryCode=".$postdata['country']."&addressLocality=".$postdata['city']."&postalCode:=".$postdata['p_code'],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
			"DHL-API-Key: 616NDjRmUOcAclqRyonX6lwm9JlnvPTW"
		],
	]);

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);

	if ($err) {
		echo "cURL Error #:" . $err;
	} else {
		return $response;
	}
	
}	


	function check_odd_no($number){
			
		$number  = end(explode('-',$number));
		$numbers  = end(str_split($number,1));
		
		if($numbers % 2 == 0){
			$test = "Even"; 
		}else{
			$test =  "Odd";	
		}
		return $test;
	}
}