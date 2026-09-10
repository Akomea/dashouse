import nodemailer from "nodemailer";
import { z } from "zod";
import { fail, ok } from "@/lib/api";

const optionalText = z.string().trim().max(2000).optional().default("");

const adoptSchema = z
  .object({
    fullName: z.string().trim().min(1).max(200),
    dateOfBirth: z.string().trim().min(1).max(50),
    phone: z.string().trim().min(1).max(50),
    email: z.string().trim().email().max(200),
    address: z.string().trim().min(1).max(300),
    cityPostal: z.string().trim().min(1).max(200),
    householdSize: z.enum(["1", "2", "3", "4", "5+"]),
    children: z.enum(["yes", "no"]),
    childrenAges: optionalText,
    otherAnimals: z.enum(["yes", "no"]),
    otherAnimalsDetail: optionalText,
    homeType: z.enum(["apartment", "house"]),
    livingSpace: z.string().trim().min(1).max(50),
    balcony: z.enum(["yes", "no"]),
    secured: z.enum(["yes", "no", "na"]),
    renting: z.enum(["yes", "no"]),
    landlordPermission: z.enum(["yes", "no", "pending", ""]).optional().default(""),
    employmentStatus: z.enum([
      "fullTime",
      "partTime",
      "selfEmployed",
      "student",
      "vocationalTraining",
      "notEmployed",
    ]),
    profession: optionalText,
    hoursAway: z.enum(["lessThan4", "hours4to6", "hours6to8", "moreThan8"]),
    hadCats: z.enum(["yes", "no"]),
    hadCatsDetail: optionalText,
    specialNeeds: z.enum(["yes", "no"]),
    whyAdopt: z.string().trim().min(1).max(2000),
    whichCats: optionalText,
    anythingElse: optionalText,
    consent: z.literal(true),
    placeDate: z.string().trim().min(1).max(200),
    signature: z.string().trim().min(1).max(200),
    locale: z.enum(["de", "en"]).optional().default("de"),
  })
  .superRefine((data, ctx) => {
    if (data.children === "yes" && !data.childrenAges) {
      ctx.addIssue({ code: "custom", path: ["childrenAges"], message: "required when children is yes" });
    }
    if (data.otherAnimals === "yes" && !data.otherAnimalsDetail) {
      ctx.addIssue({
        code: "custom",
        path: ["otherAnimalsDetail"],
        message: "required when otherAnimals is yes",
      });
    }
    if (data.renting === "yes" && !data.landlordPermission) {
      ctx.addIssue({
        code: "custom",
        path: ["landlordPermission"],
        message: "required when renting is yes",
      });
    }
    if (data.hadCats === "yes" && !data.hadCatsDetail) {
      ctx.addIssue({ code: "custom", path: ["hadCatsDetail"], message: "required when hadCats is yes" });
    }
  });

function label(locale: "de" | "en", de: string, en: string) {
  return locale === "en" ? en : de;
}

function yn(locale: "de" | "en", value: string) {
  const map: Record<string, [string, string]> = {
    yes: ["Ja", "Yes"],
    no: ["Nein", "No"],
    na: ["Nicht zutreffend", "Not applicable"],
    pending: ["Noch nicht, aber ich hole sie ein", "Not yet, but I will get it"],
    apartment: ["Wohnung", "Apartment"],
    house: ["Haus", "House"],
    fullTime: ["Vollzeit", "Full-time"],
    partTime: ["Teilzeit", "Part-time"],
    selfEmployed: ["Selbstständig", "Self-employed"],
    student: ["Student:in", "Student"],
    vocationalTraining: ["In Ausbildung", "In vocational training"],
    notEmployed: ["Nicht beschäftigt", "Not employed"],
    lessThan4: ["Weniger als 4 Stunden", "Less than 4 hours"],
    hours4to6: ["4–6 Stunden", "4–6 hours"],
    hours6to8: ["6–8 Stunden", "6–8 hours"],
    moreThan8: ["Mehr als 8 Stunden", "More than 8 hours"],
  };
  const pair = map[value];
  if (!pair) return value || "—";
  return locale === "en" ? pair[1] : pair[0];
}

function formatBody(data: z.infer<typeof adoptSchema>) {
  const loc = data.locale;
  const rows: [string, string][] = [
    [label(loc, "Name", "Name"), data.fullName],
    [label(loc, "Geburtsdatum", "Date of birth"), data.dateOfBirth],
    [label(loc, "Telefon", "Phone"), data.phone],
    [label(loc, "E-Mail", "Email"), data.email],
    [label(loc, "Adresse", "Address"), data.address],
    [label(loc, "Stadt, PLZ", "City, postal code"), data.cityPostal],
    [label(loc, "Haushaltsgröße", "Household size"), data.householdSize],
    [label(loc, "Kinder", "Children"), yn(loc, data.children)],
    [label(loc, "Alter der Kinder", "Children’s ages"), data.childrenAges || "—"],
    [label(loc, "Andere Tiere", "Other animals"), yn(loc, data.otherAnimals)],
    [label(loc, "Welche Tiere", "Which animals"), data.otherAnimalsDetail || "—"],
    [label(loc, "Wohnform", "Home type"), yn(loc, data.homeType)],
    [label(loc, "Wohnfläche m²", "Living space m²"), data.livingSpace],
    [label(loc, "Balkon/Terrasse", "Balcony/terrace"), yn(loc, data.balcony)],
    [label(loc, "Gesichert", "Secured"), yn(loc, data.secured)],
    [label(loc, "Miete", "Renting"), yn(loc, data.renting)],
    [label(loc, "Vermieter-Erlaubnis", "Landlord permission"), yn(loc, data.landlordPermission || "")],
    [label(loc, "Beschäftigung", "Employment"), yn(loc, data.employmentStatus)],
    [label(loc, "Beruf", "Profession"), data.profession || "—"],
    [label(loc, "Außer Haus", "Hours away"), yn(loc, data.hoursAway)],
    [label(loc, "Katzen zuvor", "Had cats before"), yn(loc, data.hadCats)],
    [label(loc, "Details zu früheren Katzen", "Previous cats detail"), data.hadCatsDetail || "—"],
    [label(loc, "Special needs Erfahrung", "Special needs experience"), yn(loc, data.specialNeeds)],
    [label(loc, "Motivation", "Why adopt"), data.whyAdopt],
    [label(loc, "Interessierte Katze(n)", "Cat(s) of interest"), data.whichCats || "—"],
    [label(loc, "Sonstiges", "Anything else"), data.anythingElse || "—"],
    [label(loc, "Ort, Datum", "Place, date"), data.placeDate],
    [label(loc, "Unterschrift", "Signature"), data.signature],
    [label(loc, "Einwilligung", "Consent"), label(loc, "Ja", "Yes")],
  ];

  const text = rows.map(([k, v]) => `${k}: ${v}`).join("\n");
  const html = `
    <h2>${label(loc, "Katzen-Adoptionsantrag", "Cat Adoption Application")}</h2>
    <table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;font-family:sans-serif;font-size:14px;">
      ${rows
        .map(
          ([k, v]) =>
            `<tr><td style="background:#f5f0ea;font-weight:600;vertical-align:top;">${escapeHtml(k)}</td><td>${escapeHtml(v).replace(/\n/g, "<br>")}</td></tr>`
        )
        .join("")}
    </table>
  `;
  return { text, html };
}

function escapeHtml(value: string) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const parsed = adoptSchema.safeParse(body);
    if (!parsed.success) {
      const missing = parsed.error.issues
        .map((i) => (i.path.length ? String(i.path[0]) : i.message))
        .filter(Boolean);
      return fail(
        missing.length
          ? `Please check these fields: ${[...new Set(missing)].join(", ")}`
          : "Invalid application"
      );
    }

    if (!process.env.SMTP_HOST || !process.env.SMTP_PORT || !process.env.SMTP_USER || !process.env.SMTP_PASS) {
      return fail("SMTP configuration is missing", 500);
    }

    const data = parsed.data;
    const { text, html } = formatBody(data);

    const transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT),
      secure: Number(process.env.SMTP_PORT) === 465,
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS,
      },
    });

    const recipient =
      process.env.CONTACT_TO_EMAIL ||
      "info@dashouse.at,kakomea@yahoo.com";
    await transporter.sendMail({
      from: process.env.CONTACT_FROM_EMAIL || process.env.SMTP_USER,
      to: recipient.split(",").map((e) => e.trim()).filter(Boolean),
      replyTo: data.email,
      subject: `Das House Adoption: ${data.fullName}`,
      text,
      html,
    });

    return ok({ message: "Application sent successfully" });
  } catch (error) {
    return fail(`Failed to send application: ${(error as Error).message}`, 500);
  }
}
