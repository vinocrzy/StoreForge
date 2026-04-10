#!/usr/bin/env node
// mcp-asset-service — asset management MCP server
// Provides tools for downloading, storing, optimizing, and managing image assets.

import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { registerDownloadTool } from './tools/download.js';
import { registerGetAssetsTool, registerGetManifestTool } from './tools/get-assets.js';
import { registerOptimizeTool } from './tools/optimize.js';
import { registerDeleteTool } from './tools/delete.js';

const server = new McpServer({
  name: 'storeforge-assets',
  version: '1.0.0',
});

registerDownloadTool(server);
registerGetAssetsTool(server);
registerGetManifestTool(server);
registerOptimizeTool(server);
registerDeleteTool(server);

async function gracefulShutdown(signal: string): Promise<void> {
  process.stderr.write(`[mcp-asset-service] Received ${signal}, shutting down…\n`);
  process.exit(0);
}

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

const transport = new StdioServerTransport();
await server.connect(transport);
process.stderr.write('[mcp-asset-service] Ready\n');
