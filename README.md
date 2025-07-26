# pdf2p2  

PDFs to Posts, there was already a PDF2Post plugin, so we decided to append our plugin with a 2 and shorten it a bit, so we now have pdf2p2.

## What is it? 

A tool for...
- Parsing an RSS feed URL, for a list of PDF files.
- Grabbing a PDF file from a single file URL, or from a list of PDF file URLs.
- Adding the PDF to the media libary, and creating a post with the file name, original URL and hash saved.
- Automatically grabs all the unimported PDFs from the RSS feed with a Cron job.  
- TO DO: Connecting to the Minstal OCR API and sending a PDF file with an API key.
- TO DO: Recieveing mark down content back from the Minstral API, along with any images returned.
- TO DO: Parsing the MD into the Guttenberg format.
- TO DO: Setting the status of the post from, unprocessed, processed, human verified and staff verified.
- TO DO: Provide access to another WordPress site to collect the data.
- TO DO: Remove old PDFs but keep the file hashes, dates and file names.
- TO DO: create a page for converting a single PDF that is not online, and create a DRAFT.  

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



