# HistorizationBundle to log CRUD Changes on Desired Entities

## Changes Required


Run SQL:
```sql
     ALTER SEQUENCE historization_update_sets_id_s INCREMENT BY 1;
     ALTER SEQUENCE historization_change_log_id_se INCREMENT BY 1;
     CREATE TABLE historization_update_sets (id NUMBER(10) NOT NULL, change_log_history_id NUMBER(10) DEFAULT NULL NULL, column_name VARCHAR2(255) NOT NULL, old_record VARCHAR2(255) NOT NULL, new_record VARCHAR2(255) NOT NULL, PRIMARY KEY(id));
     CREATE INDEX IDX_4645F1F3A6B2C406 ON historization_update_sets (change_log_history_id);
     CREATE TABLE historization_change_log (id NUMBER(10) NOT NULL, action_type NUMBER(10) NOT NULL, class_id NUMBER(10) NOT NULL, class_name VARCHAR2(255) NOT NULL, user_id NUMBER(10) NOT NULL, created_at NUMBER(10) NOT NULL, PRIMARY KEY(id));
     ALTER TABLE historization_update_sets ADD CONSTRAINT FK_4645F1F3A6B2C406 FOREIGN KEY (change_log_history_id) REFERENCES historization_change_log (id);
```

Add to composer.json 
     inside "psr-4" under "autoload"            

     "HistorizationBundle\\": "src/HistorizationBundle"
     
Add to AppKernel.php

     new \HistorizationBundle\HistorizationBundle()
     
Add to routing.yml

    imports:
        resource: "@HistorizationBundle/Resources/config/routing.yml"     

Add to config.yml
    under mappings:
    
            HistorizationBundle:
                dir: .
                is_bundle: true
                mapping: true
                prefix: HistorizationBundle
                type: annotation
                
Run 

    composer dump-autoload                 
                                
                
## Usage
                
                
Add This Annotation to an Entity Class you want to keep on track
```sql
 * @Config(
 *      historizable="true"
 * )
```

Example:

```sql
/**
 * @Config(
 *      historizable="true"
 * )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CategoryRepository"
 * @ORM\Table(name="Category")
 */
class Category
```

For Entities Joined add JoinConfig annotation and set historizeColumnName option to column you want to record on connected Entity
```sql
 * @JoinConfig(historizeColumnName="name")
```
Example: 

```sql
    /**
     * One Cart has One Customer.
     * @JoinConfig(historizeColumnName="name")
     * @OneToOne(targetEntity="Customer", inversedBy="cart")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;
```    

#### Get Historization Logs from Those Endpoints:

RequestType: GET 
Response: Json <br /><b>Response of Changes maded by Logged In User</b>

    /historizationBundle/api/getHistorizationRecordsOfUser name="historization_bundle_get_change_log_records_of_user"


RequestType: Post with Entity data<br /><b>Json Response of Changes maded on Given Entity</b>
    
    /historizationBundle/api/getHistorizationRecordsOfUser name="historization_bundle_get_change_log_records_of_entity"          

If you want to add It to another project add this to composer.json
```json
    "repositories" : [{
        "type" : git
        "url" : "git@github.com/gozgunes/hist..."
    }],
```          
<br />
