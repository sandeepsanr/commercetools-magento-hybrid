<?php
namespace Tricon\HelloProduct\Block;
 
class HelloProduct extends \Magento\Framework\View\Element\Template
{
    
	public function getHelloProduct()
    {
		//    $objectManager =   \Magento\Framework\App\ObjectManager::getInstance();
		//	$connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION'); 
		//	$result1 = $connection->fetchAll("SELECT * FROM rahul_magento_poc_table");
		//	echo "<pre>";
			echo "11111111111111111111111";
		//	print_r($result1);
			
    /*    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productCollection = $objectManager->create('\Magento\Catalog\Model\Product')->getCollection();
        /** Apply filters here
        
        $productCollection
        ->addAttributeToSelect('sku')
        ->addAttributeToSelect('name');
        
        */ 

			$curl = curl_init();
			
			curl_setopt_array($curl, array(
			    CURLOPT_URL => "https://api.sphere.io/tennis-45/carts/b0af12ff-5ccd-485e-a820-597ce3536772",
			    CURLOPT_RETURNTRANSFER => true,
			    CURLOPT_ENCODING => "",
			    CURLOPT_MAXREDIRS => 10,
			    CURLOPT_TIMEOUT => 30,
			    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			    CURLOPT_CUSTOMREQUEST => "GET",
			    CURLOPT_HTTPHEADER => array(
			        "authorization: Bearer Ca0lhIDj4XMDmhvs9LEumn9ThHT7Gqpp",
			        "cache-control: no-cache",
			        "postman-token: ca297502-7ee6-3b97-1caf-2139238f49cb"
			    ),
			));
			
			$response = curl_exec($curl);
			$err = curl_error($curl);
			
			curl_close($curl);
			
			if ($err) {
			    echo "cURL Error #:" . $err;
			} else {
			    echo $response;
			    echo $response;
			    $obj = json_decode($response);
			    
			    echo "\n";
			    echo "11111111111";
			    echo "\n";
			    echo $obj->{'type'};
			    echo "\n";
			    echo $obj->{'totalPrice'}->{'centAmount'};
			    echo "\n";
			    echo $obj->{'customLineItems'}[0]->{'slug'}; 
			 
			}
			
			
		/* 	$curl = curl_init();
			
			curl_setopt_array($curl, array(
			    CURLOPT_URL => "https://api.sphere.io/tennis-45/carts/b0af12ff-5ccd-485e-a820-597ce3536772",
			    CURLOPT_RETURNTRANSFER => true,
			    CURLOPT_ENCODING => "",
			    CURLOPT_MAXREDIRS => 10,
			    CURLOPT_TIMEOUT => 30,
			    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			    CURLOPT_CUSTOMREQUEST => "POST",
			    CURLOPT_POSTFIELDS => "{\r\n  \"version\": 3,\r\n  \"actions\": [{\r\n    \"action\": \"addCustomLineItem\",\r\n    \"name\": {\r\n      \"en\": \"bat-75 cm\"\r\n    },\r\n    \"quantity\": 1,\r\n    \"money\": {\r\n        \"currencyCode\": \"USD\",\r\n        \"centAmount\":100\r\n      },\r\n    \"slug\": \"bat-75 cm\",\r\n    \"taxCategory\":{\r\n    \r\n            \"id\": \"6fa94475-0188-467a-82dc-25db50a94f43\",\r\n            \"version\": 1,\r\n            \"name\": \"standard\",\r\n            \"typeId\":\"tax-category\",\r\n            \"description\": \"standard tax applied\",\r\n            \"rates\": [\r\n                {\r\n                    \"name\": \"5% US\",\r\n                    \"amount\": 0.05,\r\n                    \"includedInPrice\": false,\r\n                    \"country\": \"US\",\r\n                    \"id\": \"resbFC0z\",\r\n                    \"subRates\": []\r\n                },\r\n                {\r\n                    \"name\": \"19% MwSt\",\r\n                    \"amount\": 0.19,\r\n                    \"includedInPrice\": true,\r\n                    \"country\": \"DE\",\r\n                    \"id\": \"1Yyb7ROs\",\r\n                    \"subRates\": []\r\n                }\r\n            ]\r\n    } \r\n  }]\r\n}\r\n",
			    CURLOPT_HTTPHEADER => array(
			        "authorization: Bearer TVkUn-0u_CnAG4_5z_ZrfQjg7czq6r7x",
			        "cache-control: no-cache",
			        "postman-token: 513fbef3-57eb-9c41-4fcc-1db3e3ab5960"
			    ),
			));
			
			$response = curl_exec($curl);
			$err = curl_error($curl);
			
			curl_close($curl);
			
			if ($err) {
			    echo "cURL Error #:" . $err;
			} else {
			    echo $response;
			}
			
			
			
			 */
			
			
        
        return "product lisxt:";
        
    }
	
}
