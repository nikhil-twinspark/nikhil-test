<?php 
    
    $sessionAdmin = $this->Session->read('Admin'); 
    
    echo $this->Html->css(CDN.'css/jquery.dataTables.css');
    echo $this->Html->script(CDN.'js/jquery.dataTables.js');
    echo $this->Html->script(CDN.'js/jquery.dataTables.columnFilter.js');
    
    //echo $this->Html->css(CDN.'css/jquery-ui.css');
    //echo $this->Html->script(CDN.'js/jquery-ui.js');
    
 
?>
    <div class="contArea Clearfix">
      <div class="page-header">
        <h1>
            <i class="menu-icon fa fa-asterisk"></i> Characteristics / Insurances / Procedures
        </h1>
    </div>
    <?php 
        //echo $this->element('messagehelper'); 
        echo $this->Session->flash('good');
        echo $this->Session->flash('bad');
    ?>
        
     <div class="adminsuper">
            <span style='float:right;' class="add_rewards" >
                <a href="<?php echo $this->Html->url(array("controller"=>"CharacteristicsInsurancesManagement","action"=>"add"));?>" class="icon-add info-tooltip">Add Characteristics / Insurances / Procedures</a>
            </span>
	
		
       <table width="100%" border="0" cellpadding="0" cellspacing="0"  id="example" class="table table-striped table-bordered table-hover"> 
       <thead>
                        <tr> 
                            <td width="15%" class="client sorting" aria-label="Domain: activate to sort column ascending" >Name</td>
                            <td width="15%" class="client sorting" aria-label="Domain: activate to sort column ascending" >Type</td>
                            <td width="20%" class="aptn sorting" aria-label="Domain: activate to sort column ascending" >Options</td>
                            
                           
                         </tr>
                 </thead>
                 <tbody>
        <?php 
				
			
					foreach ($charinsu as $charinsus)
					{
					
					?>
        <tr> 
          <td width="30%"><?php echo $charinsus['CharacteristicInsurance']['name'];?></td>
          <td width="30%"><?php echo $charinsus['CharacteristicInsurance']['type'];?></td>
         <td width="15%" class="managementbtn"><a href="<?php echo $this->Html->url(array(
							    "controller" => "CharacteristicsInsurancesManagement",
							    "action" => "edit",
							    $charinsus['CharacteristicInsurance']['id']
							));?>" title="Edit Characteristics / Insurances / Procedures" class="btn btn-xs btn-info"><i class='ace-icon glyphicon glyphicon-pencil'></i></a>
                                                          <a title="Delete" href="<?= Staff_Name ?>CharacteristicsInsurancesManagement/delete/<?php echo $charinsus['CharacteristicInsurance']['id'] ?>"  class="btn btn-xs btn-danger"><i class='ace-icon glyphicon glyphicon-trash'></i></a>
       </td>
   
         			
		
        </tr>
      <?php 	
					}//Endforeach
				 ?>
        </tbody>
       </table>

   
			
     </div>
     
   </div>
   </div><!-- container -->
   
   
 <script>


   $(document).ready( function () {
        //$('a[rel*=facebox]').facebox(); //this is for light box
       $('#example').dataTable( {

           
      
    "aaSorting": [[ 0, "asc" ]],
        
     
              
                "sPaginationType": "full_numbers",
                
               
                
} );
        $('#example').dataTable().columnFilter({
                aoColumns: [ { type: "text" },
                             { type: "select" },
                             { type: "text" },
                             { type: "number" },
                             null,
                            { type: "text" },
                             null
                        ]

        });
    });
    
   </script>















