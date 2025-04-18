const fs = require("fs").promises;
const axios = require("axios");

// Configuration
const config = {
  wordpressUrl: "https://snips.snipvault.co/", // Update with your WordPress URL
  username: "jalazizexi9384", // Update with your WordPress username
  password: "uMRR hZ1c pRDO Mw5Q hm4i Ksiz", // Use an application password
  snippetsFile: "./snippets.json", // Path to your JSON file
};

async function importSnippets() {
  try {
    // Read the JSON file
    const fileContent = await fs.readFile(config.snippetsFile, "utf8");
    const { snippets } = JSON.parse(fileContent);

    // Set up axios with authentication
    const api = axios.create({
      baseURL: `${config.wordpressUrl}/wp-json/wp/v2`,
      auth: {
        username: config.username,
        password: config.password,
      },
      headers: {
        "Content-Type": "application/json",
      },
    });

    console.log(`Starting import of ${snippets.length} snippets...`);

    // Process each snippet
    for (const snippet of snippets) {
      try {
        const postData = {
          title: snippet.title,
          status: "publish",
          content: snippet.description,
          meta: {
            snippet_code: snippet.code,
            snippet_language: snippet.language,
          },
        };

        // Create the snippet post
        const response = await api.post("/snippets", postData);

        console.log(`✓ Successfully imported: ${snippet.title} (ID: ${response.data.id})`);
      } catch (error) {
        console.error(`✗ Failed to import ${snippet.title}:`);
        if (error.response) {
          console.error(`  Status: ${error.response.status}`);
          console.error(`  Message: ${JSON.stringify(error.response.data.message || error.response.data)}`);
        } else {
          console.error(`  Error: ${error.message}`);
        }
      }
    }

    console.log("\nImport complete!");
  } catch (error) {
    if (error.code === "ENOENT") {
      console.error(`Error: Could not find file ${config.snippetsFile}`);
    } else {
      console.error("Error reading or parsing JSON file:", error.message);
    }
    process.exit(1);
  }
}

// Add error handling for unhandled promises
process.on("unhandledRejection", (error) => {
  console.error("Unhandled promise rejection:", error);
  process.exit(1);
});

// Run the import
importSnippets();
