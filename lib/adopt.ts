import { z } from "zod";

const optionalText = z.string().trim().max(2000).optional().default("");

export const adoptFieldKeys = [
  "fullName",
  "dateOfBirth",
  "phone",
  "email",
  "address",
  "cityPostal",
  "householdSize",
  "children",
  "childrenAges",
  "otherAnimals",
  "otherAnimalsDetail",
  "homeType",
  "livingSpace",
  "balcony",
  "secured",
  "renting",
  "landlordPermission",
  "employmentStatus",
  "profession",
  "hoursAway",
  "hadCats",
  "hadCatsDetail",
  "specialNeeds",
  "whyAdopt",
  "whichCats",
  "anythingElse",
  "consent",
  "placeDate",
  "signature",
] as const;

export type AdoptFieldKey = (typeof adoptFieldKeys)[number];
export type AdoptFieldErrors = Partial<Record<AdoptFieldKey, string>>;

export const adoptSchema = z
  .object({
    fullName: z.string().trim().min(1, "required").max(200),
    dateOfBirth: z.string().trim().min(1, "required").max(50),
    phone: z.string().trim().min(1, "required").max(50),
    email: z.string().trim().min(1, "required").email("email").max(200),
    address: z.string().trim().min(1, "required").max(300),
    cityPostal: z.string().trim().min(1, "required").max(200),
    householdSize: z.enum(["1", "2", "3", "4", "5+"], { required_error: "required", invalid_type_error: "required" }),
    children: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    childrenAges: optionalText,
    otherAnimals: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    otherAnimalsDetail: optionalText,
    homeType: z.enum(["apartment", "house"], { required_error: "required", invalid_type_error: "required" }),
    livingSpace: z.string().trim().min(1, "required").max(50),
    balcony: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    secured: z.enum(["yes", "no", "na"], { required_error: "required", invalid_type_error: "required" }),
    renting: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    landlordPermission: z.enum(["yes", "no", "pending", ""]).optional().default(""),
    employmentStatus: z.enum(
      ["fullTime", "partTime", "selfEmployed", "student", "vocationalTraining", "notEmployed"],
      { required_error: "required", invalid_type_error: "required" }
    ),
    profession: optionalText,
    hoursAway: z.enum(["lessThan4", "hours4to6", "hours6to8", "moreThan8"], {
      required_error: "required",
      invalid_type_error: "required",
    }),
    hadCats: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    hadCatsDetail: optionalText,
    specialNeeds: z.enum(["yes", "no"], { required_error: "required", invalid_type_error: "required" }),
    whyAdopt: z.string().trim().min(1, "required").max(2000),
    whichCats: optionalText,
    anythingElse: optionalText,
    consent: z.literal(true, {
      errorMap: () => ({ message: "consent" }),
    }),
    placeDate: z.string().trim().min(1, "required").max(200),
    signature: z.string().trim().min(1, "required").max(200),
    locale: z.enum(["de", "en"]).optional().default("de"),
  })
  .superRefine((data, ctx) => {
    if (data.children === "yes" && !data.childrenAges?.trim()) {
      ctx.addIssue({ code: "custom", path: ["childrenAges"], message: "required" });
    }
    if (data.otherAnimals === "yes" && !data.otherAnimalsDetail?.trim()) {
      ctx.addIssue({ code: "custom", path: ["otherAnimalsDetail"], message: "required" });
    }
    if (data.renting === "yes" && !data.landlordPermission) {
      ctx.addIssue({ code: "custom", path: ["landlordPermission"], message: "required" });
    }
    if (data.hadCats === "yes" && !data.hadCatsDetail?.trim()) {
      ctx.addIssue({ code: "custom", path: ["hadCatsDetail"], message: "required" });
    }
  });

export type AdoptPayload = z.infer<typeof adoptSchema>;

export function issuesToFieldErrors(issues: z.ZodIssue[]): AdoptFieldErrors {
  const errors: AdoptFieldErrors = {};
  for (const issue of issues) {
    const key = String(issue.path[0] ?? "") as AdoptFieldKey;
    if (!key || !(adoptFieldKeys as readonly string[]).includes(key) || errors[key]) continue;
    const msg = issue.message || "required";
    // Empty radios/selects often come through as invalid_enum — treat as required
    if (
      msg === "required" ||
      msg === "email" ||
      msg === "consent" ||
      issue.code === "invalid_enum_value" ||
      issue.code === "invalid_type" ||
      issue.code === "invalid_literal"
    ) {
      if (key === "email" && issue.code === "invalid_string") {
        errors[key] = "email";
      } else if (key === "consent") {
        errors[key] = "consent";
      } else if (msg === "email") {
        errors[key] = "email";
      } else if (msg === "consent") {
        errors[key] = "consent";
      } else {
        errors[key] = "required";
      }
    } else {
      errors[key] = msg;
    }
  }
  return errors;
}

export function validateAdoptPayload(body: unknown) {
  const parsed = adoptSchema.safeParse(body);
  if (parsed.success) {
    return { success: true as const, data: parsed.data };
  }
  return {
    success: false as const,
    fieldErrors: issuesToFieldErrors(parsed.error.issues),
  };
}
