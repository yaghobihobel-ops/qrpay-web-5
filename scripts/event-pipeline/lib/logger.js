const pino = require('pino');

function createLogger() {
  const level = process.env.EVENT_PIPELINE_LOG_LEVEL || process.env.LOG_LEVEL || 'info';
  let transport;
  if (process.env.NODE_ENV !== 'production') {
    try {
      require.resolve('pino-pretty');
      transport = {
        target: 'pino-pretty',
        options: {
          colorize: true,
          translateTime: 'SYS:standard'
        }
      };
    } catch (error) {
      transport = undefined;
    }
  }

  return pino({
    level,
    name: 'event-pipeline-worker',
    transport
  });
}

module.exports = {
  createLogger
};
