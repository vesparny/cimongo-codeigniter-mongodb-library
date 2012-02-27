### A CodeIgniter library for interact with MongoDb.
---------------------------------------------------
	VERSION 1.2.0 STABLE
	
	Inspired by https://github.com/alexbilbie/codeigniter-mongodb-library
	
	A bit more explained documentation will be soon available.
	
	Install like every other CI library

	--Under development---

Available Functions
-------------------

### Selecting Data

	get					
	get_where		
	
	select				
	
	where		-> take a look at the comment in the code for usage details		
	or_where			
	where_in		
	or_where_in
	
	like			
	or_like				
	not_like		
	or_not_like			
	
	limit	
	
	order_by			
	
	count_all_results	
	count_all			

### Query Results

	result				
	result_array		
	result_object		
	
	row					
	row_array	

	insert_id		
	

### Result Helper

	num_rows
	has_error		

### Modifying Data

	set					
	insert				
	insert_batch		
	update				
	update_batch		
	delete				
	
### Extra methods
	command
	ensure_index
	remove_index
	remove_all_indexes
	list_indexes
	get_dbref
	create_dbref
	where_gt
	where_gte
	where_lt
	where_lte
	where_between
	where_between_ne
	where_ne
	where_near
	inc
	dec
	unset_field
	add_to_set
	push
	push_all
	pull
	pull_all
	pop
	rename_field
	