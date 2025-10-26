const fs = require('fs');
const path = require('path');
const { EventEmitter, once } = require('events');

const emitter = new EventEmitter();

async function createEventConsumer({ logger }) {
  const driver = (process.env.EVENT_STREAM_DRIVER || 'log').toLowerCase();

  if (driver === 'kafka') {
    const start = await bootstrapKafka(logger);
    return createStreamingConsumer(start, logger);
  }

  if (driver === 'nats') {
    const start = await bootstrapNats(logger);
    return createStreamingConsumer(start, logger);
  }

  return createStreamingConsumer(() => bootstrapFile(logger), logger);
}

async function bootstrapKafka(logger) {
  try {
    const { Kafka } = require('kafkajs');
    const clientId = process.env.EVENT_PIPELINE_KAFKA_CLIENT || 'qrpay-event-pipeline';
    const brokers = (process.env.EVENT_STREAM_KAFKA_BROKERS || 'localhost:9092').split(',');
    const kafka = new Kafka({ clientId, brokers });
    const consumer = kafka.consumer({ groupId: process.env.EVENT_PIPELINE_KAFKA_GROUP || 'qrpay-event-pipeline-group' });
    await consumer.connect();
    const topic = process.env.EVENT_PIPELINE_TOPIC || process.env.EVENT_STREAM_DEFAULT_DESTINATION || 'qrpay.transactions';
    await consumer.subscribe({ topic, fromBeginning: false });

    return async function start() {
      await consumer.run({
        eachMessage: async ({ message }) => {
          if (message.value) {
            dispatch(message.value.toString(), logger);
          }
        }
      });
    };
  } catch (error) {
    logger.warn({ err: error }, 'Kafka consumer unavailable; falling back to file consumer');
    return () => bootstrapFile(logger);
  }
}

async function bootstrapNats(logger) {
  try {
    const { connect } = require('nats');
    const url = process.env.EVENT_STREAM_NATS_URL || 'nats://127.0.0.1:4222';
    const subject = process.env.EVENT_PIPELINE_TOPIC || process.env.EVENT_STREAM_DEFAULT_DESTINATION || 'qrpay.transactions';
    const connection = await connect({ servers: url, name: 'qrpay-event-pipeline' });

    return async function start() {
      const subscription = connection.subscribe(subject);
      (async () => {
        for await (const message of subscription) {
          const payload = message.data ? Buffer.from(message.data).toString() : null;
          if (payload) {
            dispatch(payload, logger);
          }
        }
      })().catch((err) => logger.error({ err }, 'NATS subscription failed'));
    };
  } catch (error) {
    logger.warn({ err: error }, 'NATS consumer unavailable; falling back to file consumer');
    return () => bootstrapFile(logger);
  }
}

function bootstrapFile(logger) {
  const filePath = path.resolve(process.cwd(), 'storage', 'app', process.env.EVENT_PIPELINE_FILE || 'event-stream/qrpay-transactions.jsonl');
  logger.info({ filePath }, 'Using file-based event consumer');

  (async () => {
    let position = 0;
    let buffer = '';

    while (true) {
      await delay(Number(process.env.EVENT_PIPELINE_POLL_INTERVAL || 1500));

      if (!fs.existsSync(filePath)) {
        continue;
      }

      const stats = fs.statSync(filePath);
      if (stats.size <= position) {
        continue;
      }

      const stream = fs.createReadStream(filePath, { start: position, end: stats.size });
      for await (const chunk of stream) {
        buffer += chunk.toString();
        let newlineIndex;
        while ((newlineIndex = buffer.indexOf('\n')) >= 0) {
          const line = buffer.slice(0, newlineIndex);
          buffer = buffer.slice(newlineIndex + 1);
          if (line.trim()) {
            dispatch(line, logger);
          }
        }
      }

      position = stats.size;
    }
  })().catch((err) => logger.error({ err }, 'File consumer failure'));
}

function createStreamingConsumer(start, logger) {
  let started = false;

  return {
    async *consume() {
      if (!started) {
        started = true;
        await Promise.resolve(start());
      }

      while (true) {
        const [event] = await once(emitter, 'event');
        yield event;
      }
    }
  };
}

function dispatch(payload, logger) {
  try {
    const parsed = JSON.parse(payload);
    emitter.emit('event', parsed);
  } catch (error) {
    logger.warn({ err: error, payload }, 'Failed to parse event payload');
  }
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

module.exports = {
  createEventConsumer
};
