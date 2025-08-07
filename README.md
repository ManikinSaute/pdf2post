# pdf2p2     

PDFs to Posts, there was already a PDF2Post plugin, so we decided to append our plugin with a 2 and shorten it a bit, so we now have pdf2p2.

## What is it? 

A tool for...
- Parsing a RSS feed URL to extrack a list of PDF files.   
- Grabbing the PDFs and saving them to the media libary.
- Creating a post with the file name, original URL, attachment and hash saved.
- Sending the original PDF file URL to the Minstal OCR tool.
- Populating the post content with the results from the OCR tool.
- Converting the MD to HTML and then Gutenberg.
- A single post can be viewed, with meta data saved on the right hand side of the single post.
- A single post can be accessed as JSON 
- TO DO: Provide access to another WordPress site to collect the data.
- TO DO: Remove old PDFs but keep the file hashes, dates and file names.
- TO DO: Show an index page with all avalibe processes & staff verified pages.
- TO DO: Show an index page with all avalibe processes & un verified pages.

## Demo & Test

- Open the [latest version](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ManikinSaute/pdf2p2/main/blueprint.json ) in playground!
- Open the [latest stable version](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ManikinSaute/pdf2p2/main/blueprint-stable.json ) in playground!
- You can make changes to [file](https://github.com/ManikinSaute/pdf2p2/blob/main/pdf2p2.php) locally and paste them into the file editor within WP playground, deactive and -reactivate if you cant see your changes.
- You can edit the [blue print here ](https://github.com/ManikinSaute/pdf2p2/blob/main/blueprint.json    )
- You can [test edits to a blue print here](https://playground.wordpress.net/builder/builder.html   ) 
- If a commit to main contains the word "zip-it" the Zip file will be created and the playground link will be updated to run the code from main.
 
## Some other stuff the plugin does 

- Registers an Import Custom Post Type, this is where the original data will be saved in MD format  
- Registers a Gutenberg Custom Post Type, this is where we will save the Gutenberg version of the content  
- Creates a sidebar settings tab with...
- A dashboard 
- Logging
- Settings
- Single import
- Bulk import
- RSS feed view
- Registers a custom taxonomy
- Unprocessed
- Processed
- Human verified
- Staff verified     

Thanks :-) 



