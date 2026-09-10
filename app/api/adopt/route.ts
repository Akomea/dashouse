import { fail, ok } from "@/lib/api";
import { validateAdoptPayload, type AdoptPayload } from "@/lib/adopt";
import { saveAdoptionApplication } from "@/lib/adoption-store";
import { isMailConfigured, sendMail } from "@/lib/mail";

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

function formatBody(data: AdoptPayload) {
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
    const parsed = validateAdoptPayload(body);
    if (!parsed.success) {
      return fail("Please check the highlighted fields.", 400, {
        code: "VALIDATION_ERROR",
        fieldErrors: parsed.fieldErrors,
      });
    }

    const data = parsed.data;
    const saved = await saveAdoptionApplication(data);

    // Email is optional — DB save is what makes submit succeed
    let emailed = false;
    if (isMailConfigured()) {
      try {
        const { text, html } = formatBody(data);
        await sendMail({
          subject: `Das House Adoption: ${data.fullName}`,
          text,
          html,
          replyTo: data.email,
        });
        emailed = true;
      } catch {
        emailed = false;
      }
    }

    return ok({
      message: "Application saved successfully",
      id: saved?.id,
      emailed,
    });
  } catch (error) {
    return fail(`Failed to save application: ${(error as Error).message}`, 500, {
      code: "SAVE_FAILED",
    });
  }
}
