const fs = require('fs');
const path = require('path');

async function createWarehouseClient({ logger }) {
  const driver = (process.env.DATA_WAREHOUSE_DRIVER || 'filesystem').toLowerCase();

  if (driver === 'bigquery') {
    const client = await bootstrapBigQuery(logger);
    if (client) {
      return client;
    }
  }

  if (driver === 'clickhouse') {
    const client = await bootstrapClickHouse(logger);
    if (client) {
      return client;
    }
  }

  return createFileWarehouse(logger);
}

async function bootstrapBigQuery(logger) {
  try {
    const { BigQuery } = require('@google-cloud/bigquery');
    const datasetId = process.env.BIGQUERY_DATASET || 'qrpay_events';
    const tableId = process.env.BIGQUERY_TABLE || 'transactions';
    const bigquery = new BigQuery();

    return {
      async persist(event) {
        await bigquery.dataset(datasetId).table(tableId).insert(event);
      }
    };
  } catch (error) {
    logger.warn({ err: error }, 'BigQuery client unavailable; falling back to filesystem warehouse');
    return null;
  }
}

async function bootstrapClickHouse(logger) {
  try {
    const { createClient } = require('@clickhouse/client');
    const host = process.env.CLICKHOUSE_URL || 'http://localhost:8123';
    const client = createClient({
      host,
      username: process.env.CLICKHOUSE_USERNAME || 'default',
      password: process.env.CLICKHOUSE_PASSWORD || ''
    });
    const table = process.env.CLICKHOUSE_TABLE || 'qrpay_transactions';

    return {
      async persist(event) {
        await client.insert({
          table,
          values: [event],
          format: 'JSONEachRow'
        });
      }
    };
  } catch (error) {
    logger.warn({ err: error }, 'ClickHouse client unavailable; falling back to filesystem warehouse');
    return null;
  }
}

function createFileWarehouse(logger) {
  const outputPath = path.resolve(process.cwd(), 'storage', 'app', 'data-warehouse', 'events.jsonl');
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  logger.info({ outputPath }, 'Using filesystem warehouse sink');

  return {
    async persist(event) {
      fs.appendFileSync(outputPath, `${JSON.stringify(event)}\n`);
    }
  };
}

module.exports = {
  createWarehouseClient
};
