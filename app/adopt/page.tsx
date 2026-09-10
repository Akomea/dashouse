"use client";

import { FormEvent, ReactNode, useMemo, useState } from "react";
import { useLanguage, useT } from "@/lib/i18n/language-context";
import "./adopt.css";

type Status = "idle" | "submitting" | "success" | "error";

const PDF_HREF = "/docs/katzen-adoptionsantrag.pdf";

const emptyForm = {
  fullName: "",
  dateOfBirth: "",
  phone: "",
  email: "",
  address: "",
  cityPostal: "",
  householdSize: "",
  children: "",
  childrenAges: "",
  otherAnimals: "",
  otherAnimalsDetail: "",
  homeType: "",
  livingSpace: "",
  balcony: "",
  secured: "",
  renting: "",
  landlordPermission: "",
  employmentStatus: "",
  profession: "",
  hoursAway: "",
  hadCats: "",
  hadCatsDetail: "",
  specialNeeds: "",
  whyAdopt: "",
  whichCats: "",
  anythingElse: "",
  consent: false,
  placeDate: "",
  signature: "",
};

export default function AdoptPage() {
  const t = useT();
  const { locale } = useLanguage();
  const [form, setForm] = useState(emptyForm);
  const [status, setStatus] = useState<Status>("idle");
  const [errorMsg, setErrorMsg] = useState("");

  const f = t.adopt.fields;

  const set =
    (key: keyof typeof emptyForm) =>
    (value: string | boolean) => {
      setForm((prev) => ({ ...prev, [key]: value }));
      if (status === "error" || status === "success") setStatus("idle");
    };

  const householdOptions = useMemo(() => ["1", "2", "3", "4", "5+"], []);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setStatus("submitting");
    setErrorMsg("");

    const payload = {
      ...form,
      consent: form.consent === true,
      locale,
    };

    try {
      const res = await fetch("/api/adopt", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.success === false) {
        setStatus("error");
        setErrorMsg(typeof data.error === "string" ? data.error : t.adopt.error);
        return;
      }
      setStatus("success");
      setForm(emptyForm);
    } catch {
      setStatus("error");
      setErrorMsg(t.adopt.error);
    }
  }

  return (
    <div className="adopt-page">
      <header className="adopt-hero">
        <h1>{t.adopt.title}</h1>
        <p>{t.adopt.subtitle}</p>
        <div className="adopt-toolbar">
          <a className="adopt-btn adopt-btn-ghost" href={PDF_HREF} download>
            {t.adopt.download}
          </a>
        </div>
      </header>

      <div className="adopt-sheet-wrap">
        <div className="adopt-sheet">
          <p className="adopt-intro">{t.adopt.intro}</p>
          <p className="adopt-required-note">{t.adopt.requiredNote}</p>

          <form onSubmit={onSubmit} noValidate>
            <div className="adopt-columns">
              <div>
                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="1">
                    {t.adopt.sections.personal}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={`${f.fullName} *`}>
                      <input
                        type="text"
                        required
                        autoComplete="name"
                        value={form.fullName}
                        onChange={(e) => set("fullName")(e.target.value)}
                      />
                    </Field>
                    <div className="adopt-row two">
                      <Field label={`${f.dateOfBirth} *`}>
                        <input
                          type="date"
                          required
                          value={form.dateOfBirth}
                          onChange={(e) => set("dateOfBirth")(e.target.value)}
                        />
                      </Field>
                      <Field label={`${f.phone} *`}>
                        <input
                          type="tel"
                          required
                          autoComplete="tel"
                          value={form.phone}
                          onChange={(e) => set("phone")(e.target.value)}
                        />
                      </Field>
                    </div>
                    <Field label={`${f.email} *`}>
                      <input
                        type="email"
                        required
                        autoComplete="email"
                        value={form.email}
                        onChange={(e) => set("email")(e.target.value)}
                      />
                    </Field>
                    <Field label={`${f.address} *`}>
                      <input
                        type="text"
                        required
                        autoComplete="street-address"
                        value={form.address}
                        onChange={(e) => set("address")(e.target.value)}
                      />
                    </Field>
                    <Field label={`${f.cityPostal} *`}>
                      <input
                        type="text"
                        required
                        autoComplete="postal-code"
                        value={form.cityPostal}
                        onChange={(e) => set("cityPostal")(e.target.value)}
                      />
                    </Field>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="2">
                    {t.adopt.sections.household}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup legend={`${f.householdSize} *`} name="householdSize">
                      {householdOptions.map((n) => (
                        <Choice
                          key={n}
                          name="householdSize"
                          checked={form.householdSize === n}
                          onChange={() => set("householdSize")(n)}
                          label={n}
                          required
                        />
                      ))}
                    </ChoiceGroup>
                    <ChoiceGroup legend={`${f.children} *`} name="children">
                      <Choice
                        name="children"
                        checked={form.children === "yes"}
                        onChange={() => set("children")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="children"
                        checked={form.children === "no"}
                        onChange={() => set("children")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.children === "yes" && (
                      <Field label={`${f.childrenAges} *`}>
                        <input
                          type="text"
                          required
                          value={form.childrenAges}
                          onChange={(e) => set("childrenAges")(e.target.value)}
                        />
                      </Field>
                    )}
                    <ChoiceGroup legend={`${f.otherAnimals} *`} name="otherAnimals">
                      <Choice
                        name="otherAnimals"
                        checked={form.otherAnimals === "yes"}
                        onChange={() => set("otherAnimals")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="otherAnimals"
                        checked={form.otherAnimals === "no"}
                        onChange={() => set("otherAnimals")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.otherAnimals === "yes" && (
                      <Field label={`${f.otherAnimalsDetail} *`}>
                        <input
                          type="text"
                          required
                          value={form.otherAnimalsDetail}
                          onChange={(e) => set("otherAnimalsDetail")(e.target.value)}
                        />
                      </Field>
                    )}
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="3">
                    {t.adopt.sections.living}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup legend={`${f.homeType} *`} name="homeType">
                      <Choice
                        name="homeType"
                        checked={form.homeType === "apartment"}
                        onChange={() => set("homeType")("apartment")}
                        label={f.apartment}
                        required
                      />
                      <Choice
                        name="homeType"
                        checked={form.homeType === "house"}
                        onChange={() => set("homeType")("house")}
                        label={f.house}
                      />
                    </ChoiceGroup>
                    <Field label={`${f.livingSpace} *`}>
                      <input
                        type="text"
                        required
                        inputMode="numeric"
                        value={form.livingSpace}
                        onChange={(e) => set("livingSpace")(e.target.value)}
                      />
                    </Field>
                    <ChoiceGroup legend={`${f.balcony} *`} name="balcony">
                      <Choice
                        name="balcony"
                        checked={form.balcony === "yes"}
                        onChange={() => set("balcony")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="balcony"
                        checked={form.balcony === "no"}
                        onChange={() => set("balcony")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    <ChoiceGroup legend={`${f.secured} *`} name="secured">
                      <Choice
                        name="secured"
                        checked={form.secured === "yes"}
                        onChange={() => set("secured")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="secured"
                        checked={form.secured === "no"}
                        onChange={() => set("secured")("no")}
                        label={t.adopt.no}
                      />
                      <Choice
                        name="secured"
                        checked={form.secured === "na"}
                        onChange={() => set("secured")("na")}
                        label={t.adopt.notApplicable}
                      />
                    </ChoiceGroup>
                    <ChoiceGroup legend={`${f.renting} *`} name="renting">
                      <Choice
                        name="renting"
                        checked={form.renting === "yes"}
                        onChange={() => set("renting")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="renting"
                        checked={form.renting === "no"}
                        onChange={() => {
                          set("renting")("no");
                          set("landlordPermission")("");
                        }}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.renting === "yes" && (
                      <ChoiceGroup legend={`${f.landlordPermission} *`} name="landlordPermission">
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "yes"}
                          onChange={() => set("landlordPermission")("yes")}
                          label={t.adopt.yes}
                          required
                        />
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "no"}
                          onChange={() => set("landlordPermission")("no")}
                          label={t.adopt.no}
                        />
                        <Choice
                          name="landlordPermission"
                          checked={form.landlordPermission === "pending"}
                          onChange={() => set("landlordPermission")("pending")}
                          label={t.adopt.notYet}
                        />
                      </ChoiceGroup>
                    )}
                  </div>
                </section>
              </div>

              <div>
                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="4">
                    {t.adopt.sections.employment}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup legend={`${f.employmentStatus} *`} name="employmentStatus">
                      {(
                        [
                          ["fullTime", f.fullTime],
                          ["partTime", f.partTime],
                          ["selfEmployed", f.selfEmployed],
                          ["student", f.student],
                          ["vocationalTraining", f.vocationalTraining],
                          ["notEmployed", f.notEmployed],
                        ] as const
                      ).map(([value, label], i) => (
                        <Choice
                          key={value}
                          name="employmentStatus"
                          checked={form.employmentStatus === value}
                          onChange={() => set("employmentStatus")(value)}
                          label={label}
                          required={i === 0}
                        />
                      ))}
                    </ChoiceGroup>
                    <Field label={f.profession}>
                      <input
                        type="text"
                        value={form.profession}
                        onChange={(e) => set("profession")(e.target.value)}
                      />
                    </Field>
                    <ChoiceGroup legend={`${f.hoursAway} *`} name="hoursAway">
                      {(
                        [
                          ["lessThan4", f.lessThan4],
                          ["hours4to6", f.hours4to6],
                          ["hours6to8", f.hours6to8],
                          ["moreThan8", f.moreThan8],
                        ] as const
                      ).map(([value, label], i) => (
                        <Choice
                          key={value}
                          name="hoursAway"
                          checked={form.hoursAway === value}
                          onChange={() => set("hoursAway")(value)}
                          label={label}
                          required={i === 0}
                        />
                      ))}
                    </ChoiceGroup>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="5">
                    {t.adopt.sections.experience}
                  </div>
                  <div className="adopt-section-body">
                    <ChoiceGroup legend={`${f.hadCats} *`} name="hadCats">
                      <Choice
                        name="hadCats"
                        checked={form.hadCats === "yes"}
                        onChange={() => set("hadCats")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="hadCats"
                        checked={form.hadCats === "no"}
                        onChange={() => set("hadCats")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                    {form.hadCats === "yes" && (
                      <Field label={`${f.hadCatsDetail} *`}>
                        <textarea
                          required
                          value={form.hadCatsDetail}
                          onChange={(e) => set("hadCatsDetail")(e.target.value)}
                        />
                      </Field>
                    )}
                    <ChoiceGroup legend={`${f.specialNeeds} *`} name="specialNeeds">
                      <Choice
                        name="specialNeeds"
                        checked={form.specialNeeds === "yes"}
                        onChange={() => set("specialNeeds")("yes")}
                        label={t.adopt.yes}
                        required
                      />
                      <Choice
                        name="specialNeeds"
                        checked={form.specialNeeds === "no"}
                        onChange={() => set("specialNeeds")("no")}
                        label={t.adopt.no}
                      />
                    </ChoiceGroup>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="6">
                    {t.adopt.sections.motivation}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={`${f.whyAdopt} *`}>
                      <textarea
                        required
                        value={form.whyAdopt}
                        onChange={(e) => set("whyAdopt")(e.target.value)}
                      />
                    </Field>
                    <Field label={f.whichCats}>
                      <textarea
                        value={form.whichCats}
                        onChange={(e) => set("whichCats")(e.target.value)}
                      />
                    </Field>
                  </div>
                </section>

                <section className="adopt-section">
                  <div className="adopt-section-head" data-tone="7">
                    {t.adopt.sections.additional}
                  </div>
                  <div className="adopt-section-body">
                    <Field label={f.anythingElse}>
                      <textarea
                        value={form.anythingElse}
                        onChange={(e) => set("anythingElse")(e.target.value)}
                      />
                    </Field>
                    <div className="adopt-consent">
                      <input
                        id="adopt-consent"
                        type="checkbox"
                        required
                        checked={form.consent}
                        onChange={(e) => set("consent")(e.target.checked)}
                      />
                      <label htmlFor="adopt-consent">
                        {f.consent} *
                      </label>
                    </div>
                    <div className="adopt-footer-fields">
                      <Field label={`${f.placeDate} *`}>
                        <input
                          type="text"
                          required
                          value={form.placeDate}
                          onChange={(e) => set("placeDate")(e.target.value)}
                        />
                      </Field>
                      <Field label={`${f.signature} *`}>
                        <input
                          type="text"
                          required
                          value={form.signature}
                          onChange={(e) => set("signature")(e.target.value)}
                        />
                      </Field>
                    </div>
                  </div>
                </section>
              </div>
            </div>

            <div className="adopt-actions">
              <button
                type="submit"
                className="adopt-btn adopt-btn-primary"
                disabled={status === "submitting"}
              >
                {status === "submitting" ? t.adopt.submitting : t.adopt.submit}
              </button>
              <a className="adopt-btn adopt-btn-ghost" href={PDF_HREF} download>
                {t.adopt.download}
              </a>
            </div>

            {status === "success" && <div className="adopt-status ok">{t.adopt.success}</div>}
            {status === "error" && (
              <div className="adopt-status err">{errorMsg || t.adopt.error}</div>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="adopt-field">
      <label>
        {label}
        {children}
      </label>
    </div>
  );
}

function ChoiceGroup({
  legend,
  name,
  children,
}: {
  legend: string;
  name: string;
  children: ReactNode;
}) {
  return (
    <fieldset className="adopt-fieldset" name={name}>
      <legend>{legend}</legend>
      <div className="adopt-choices">{children}</div>
    </fieldset>
  );
}

function Choice({
  name,
  label,
  checked,
  onChange,
  required,
}: {
  name: string;
  label: string;
  checked: boolean;
  onChange: () => void;
  required?: boolean;
}) {
  return (
    <label className="adopt-choice">
      <input
        type="radio"
        name={name}
        checked={checked}
        onChange={onChange}
        required={required}
      />
      {label}
    </label>
  );
}
