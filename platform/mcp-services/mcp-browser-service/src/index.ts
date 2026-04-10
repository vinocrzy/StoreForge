// mcp-browser-service entry point

import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { registerSearchTool } from './tools/search.js';
import { registerExtractTool } from './tools/extract.js';
import { registerGetImagesTool } from './tools/get-images.js';
import { shutdown } from './browser.js';

const server = new McpServer({
  name: 'storeforge-browser',
  version: '1.0.0',
});

registerSearchTool(server);
registerExtractTool(server);
registerGetImagesTool(server);

// Graceful shutdown
const gracefulShutdown = async () => {
  await shutdown();
  process.exit(0);
};
process.on('SIGINT', gracefulShutdown);
process.on('SIGTERM', gracefulShutdown);

const transport = new StdioServerTransport();
await server.connect(transport);
