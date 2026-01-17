import express from 'express';

const app = express();
app.use(express.json({ limit: '10mb' })); // Support large email bodies

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

// Parse email endpoint
app.post('/parse-email', async (req, res) => {
  try {
    const { from, subject, body, htmlBody, replyTo } = req.body;

    // Validate required fields
    if (!from || !subject || !body) {
      return res.status(400).json({
        error: 'Missing required fields: from, subject, body'
      });
    }

    console.log(`[Parser] Processing email from: ${from}`);
    console.log(`[Parser] Subject: ${subject}`);

    // TODO: Import and use parser-router when parser files are copied from frontend
    // const { parseEmail } = await import('./src/parsers/parser-router.js');
    // 
    // const parsedOrder = await parseEmail({
    //   from,
    //   subject,
    //   body,
    //   htmlBody,
    //   replyTo,
    //   mode: 'regex', // Force regex-only (no OpenAI API calls)
    // });

    // For now, return null until parser files are copied
    const parsedOrder = null;

    if (parsedOrder) {
      console.log(`[Parser] ✅ Order extracted: ${parsedOrder.vendor} - ${parsedOrder.orderId}`);
      res.json(parsedOrder);
    } else {
      console.log(`[Parser] ℹ️ Not an order email`);
      res.json(null);
    }
  } catch (error) {
    console.error('[Parser] ❌ Error:', error);
    res.status(500).json({
      error: 'Parser failed',
      message: error.message
    });
  }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, '127.0.0.1', () => {
  console.log(`✅ Parser service running on http://localhost:${PORT}`);
});
