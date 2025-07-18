# PDF2Post

Open in playground https://playground.wordpress.net/   
Add the V1.0 plugin    
Delete Hello Dolly /wp-admin/plugins.php   
Copy plugin code https://github.com/ManikinSaute/PDF2Post/blob/main/pdf2post.php    
Paste into /wp-admin/plugin-editor.php   

A tool for grabbing a PDF file from a URL, performing OCR, and creating a WordPress post with Gutenberg blocks.

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
- Adds the file name to post meta  
- Adds the original file URL to post meta

When editing a single Import post:

- Provide a blank field for a user to manually add a unique document ID  
- Show a button for the user to **Extract OCR**  
    - When clicked, perform OCR on image-based documents using Tesseract, OCRmyPDF, or ABBYY  
    - Save data to OCR post meta  
- Show a button for the user to **Extract PDF Content**  
    - When clicked, perform text extraction using pdf2htmlEX, pdftohtml (Spatie/pdf-to-text), or Smalot/pdfparser  
    - Save extraction data to extraction post meta  
- Show a button for the user to **Build New Doc Using AI**  
    - When clicked, perform text analysis  
    - Guess which version (OCR or extracted) is better  
    - Save guess to post meta  
    - Build a more complete document from both sources  
    - Save AI-generated data to AI generation post meta  
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
