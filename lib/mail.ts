import nodemailer from "nodemailer";

export function getMailRecipients(fallback = "info@dashouse.at,kakomea@yahoo.com") {
  const raw = process.env.CONTACT_TO_EMAIL || fallback;
  return raw
    .split(",")
    .map((e) => e.trim())
    .filter(Boolean);
}

function hasWeb3Forms() {
  return Boolean(process.env.WEB3FORMS_ACCESS_KEY?.trim());
}

function hasSmtp() {
  return Boolean(
    process.env.SMTP_HOST?.trim() &&
      process.env.SMTP_PORT?.trim() &&
      process.env.SMTP_USER?.trim() &&
      process.env.SMTP_PASS?.trim()
  );
}

/** True when Web3Forms (preferred) or SMTP is ready to send */
export function isMailConfigured() {
  return hasWeb3Forms() || hasSmtp();
}

/** Kept for older imports — same as isMailConfigured */
export function isSmtpConfigured() {
  return isMailConfigured();
}

async function sendViaWeb3Forms(options: {
  subject: string;
  text: string;
  html?: string;
  replyTo?: string;
}) {
  const accessKey = process.env.WEB3FORMS_ACCESS_KEY!.trim();
  const recipients = getMailRecipients();
  const [primary, ...cc] = recipients;

  const payload: Record<string, string> = {
    access_key: accessKey,
    subject: options.subject,
    from_name: "Das House Website",
    email: options.replyTo || primary,
    message: options.text,
  };

  if (options.html) {
    payload.html = options.html;
  }

  // Inbox is the email used when creating the Web3Forms key.
  // Extra addresses (e.g. your yahoo) go on ccemail.
  if (cc.length) {
    payload.ccemail = cc.join(",");
  }
  if (primary) {
    payload.to = primary;
  }

  const res = await fetch("https://api.web3forms.com/submit", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify(payload),
  });

  const data = (await res.json().catch(() => ({}))) as {
    success?: boolean;
    message?: string;
  };

  if (!res.ok || data.success === false) {
    throw new Error(data.message || `Web3Forms failed (${res.status})`);
  }

  return recipients;
}

async function sendViaSmtp(options: {
  subject: string;
  text: string;
  html?: string;
  replyTo?: string;
}) {
  const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: Number(process.env.SMTP_PORT),
    secure: Number(process.env.SMTP_PORT) === 465,
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS,
    },
  });

  const to = getMailRecipients();
  await transporter.sendMail({
    from: process.env.CONTACT_FROM_EMAIL || process.env.SMTP_USER,
    to,
    replyTo: options.replyTo,
    subject: options.subject,
    text: options.text,
    html: options.html,
  });
  return to;
}

export async function sendMail(options: {
  subject: string;
  text: string;
  html?: string;
  replyTo?: string;
}) {
  if (hasWeb3Forms()) {
    return sendViaWeb3Forms(options);
  }
  if (hasSmtp()) {
    return sendViaSmtp(options);
  }
  throw new Error("MAIL_NOT_CONFIGURED");
}
