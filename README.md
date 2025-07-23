# pdf2p2  

## Demo & Test

- Open the [latest version](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ManikinSaute/pdf2p2/main/blueprint.json ) in playground!
- You can make changes to [file](https://github.com/ManikinSaute/pdf2p2/blob/main/pdf2p2.php) locally and paste them into the file editor within WP playground, deactive and -reactivate if you cant see your changes.
- You can edit the [blue print here ](https://github.com/ManikinSaute/pdf2p2/blob/main/blueprint.json    )
- You can [test edits to a blue print here](https://playground.wordpress.net/builder/builder.html   ) 
- If a commit to main contains the word "zip-it" the Zip file will be created and the playground link will be updated to run the code from main. 

## What is it? 

A tool for...
Parsing an RSS feed URL, for a list of PDF files.
TO DO: Grabbing a PDF file from a single file URL, or from a list of PDF file URLs.
Adding the PDF to the media libary, and creating a post with the file name, original URL and hash saved.
TO DO: Connecting to the Minstal OCR API and sending a PDF file with an API key.
TO DO: Recieveing mark down content back from the Minstral API, along with any images returned.
TO DO: Parsing the MD into the Guttenberg format.
TO DO: Setting the status of the post from, unprocessed, processed, human verified and staff verified.
TO DO: Automate the processing of new PDFs via a CRON job 


, performing OCR, and creating a WordPress post with Gutenberg blocks.

The plugin does the following: 

- Checks if post revisions are enabled  
- Registers an Import Custom Post Type — this is where the original data will be saved  
- Registers a Markdown Custom Post Type — this is where we will store the Markdown version of the content  
- Registers a Gutenberg Custom Post Type — this is where we will save the Gutenberg version of the content  
- Creates an admin page in Appearance → Tools called **Import PDF**

Admin page features:

- Field to paste in a URL for a file  
- Button to process the file  
- Creates an "Import" post  
- Saves the file to the media library  
- Adds the file name, original URL and a hash, to post meta  
- Sets the post to unprocessed 

When editing a single Import post:

- Provide a blank field for a user to manually add a unique document ID  
- Show a button for the user to **Extract OCR**  
    - When clicked, perform OCR on image-based documents using Tesseract, OCRmyPDF, or ABBYY  
    - Save data to OCR post meta  
- Show a button for the user to **Extract PDF Content**  
    - When clicked, perform text extraction using pdf2htmlEX, pdftohtml (Spatie/pdf-to-text), or Smalot/pdfparser  
    - Save extraction data to extraction post meta  
- Show a select box for OCR, Extraction, or AI, and a button  
    - When the button is clicked, move the content from post meta to the main content  
- Provide a button to **Convert Content to Markdown (MD)**  
    - Creates a Markdown post and moves post data and meta data to the new post  
- Provide a button to **Convert Content to Gutenberg**  
    - Creates a Gutenberg post and moves post data and meta data to the new post

When editing a Markdown post:

- User has a button to convert content into Markdown format  
- Can view the Markdown in a front-end template  
- Can use post revisions to check the content has not changed  
- Needs a field to record who has reviewed the content  
- Needs a field to select the date last reviewed

When editing a Gutenberg post:

- User has a button to convert content into Gutenberg format  
- Can view the WordPress content in a front-end template  
- Can use post revisions to check the content has not changed  
- Needs a field to record who has reviewed the content  
- Needs a field to select the date last reviewed

API endpoints needed:

- API endpoint to list all the documents that have been processed  
- API endpoint for a single document showing:  
    - Original file name  
    - Unique document ID  
    - Available formats  
    - URL for Markdown  
    - URL for Gutenberg format  
    - Last checked date for Markdown  
    - Last checked date for Gutenberg   
