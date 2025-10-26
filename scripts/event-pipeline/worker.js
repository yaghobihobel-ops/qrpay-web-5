#!/usr/bin/env node

const { createEventConsumer } = require('./lib/eventConsumer');
const { createWarehouseClient } = require('./lib/warehouseClient');
const { createLogger } = require('./lib/logger');

(async () => {
  const logger = createLogger();
  logger.info('Starting event pipeline worker');

  const consumer = await createEventConsumer({ logger });
  const warehouse = await createWarehouseClient({ logger });

  for await (const event of consumer.consume()) {
    try {
      await warehouse.persist(event);
      logger.debug({ event_type: event.event_type, event_id: event.event_id }, 'Persisted event to warehouse');
    } catch (error) {
      logger.error({ err: error, event }, 'Failed to persist event to warehouse');
    }
  }
})().catch((error) => {
  console.error('Fatal error in event pipeline worker', error);
  process.exit(1);
});
