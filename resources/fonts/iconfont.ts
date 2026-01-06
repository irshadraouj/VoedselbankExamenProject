export type IconfontId =
  | "twitter-x"
  | "times"
  | "search"
  | "phone"
  | "mail"
  | "linkedin"
  | "instagram"
  | "hamburger"
  | "facebook"
  | "danger"
  | "caret"
  | "arrow-right"
  | "arrow-left";

export type IconfontKey =
  | "TwitterX"
  | "Times"
  | "Search"
  | "Phone"
  | "Mail"
  | "Linkedin"
  | "Instagram"
  | "Hamburger"
  | "Facebook"
  | "Danger"
  | "Caret"
  | "ArrowRight"
  | "ArrowLeft";

export enum Iconfont {
  TwitterX = "twitter-x",
  Times = "times",
  Search = "search",
  Phone = "phone",
  Mail = "mail",
  Linkedin = "linkedin",
  Instagram = "instagram",
  Hamburger = "hamburger",
  Facebook = "facebook",
  Danger = "danger",
  Caret = "caret",
  ArrowRight = "arrow-right",
  ArrowLeft = "arrow-left",
}

export const ICONFONT_CODEPOINTS: { [key in Iconfont]: string } = {
  [Iconfont.TwitterX]: "61697",
  [Iconfont.Times]: "61698",
  [Iconfont.Search]: "61699",
  [Iconfont.Phone]: "61700",
  [Iconfont.Mail]: "61701",
  [Iconfont.Linkedin]: "61702",
  [Iconfont.Instagram]: "61703",
  [Iconfont.Hamburger]: "61704",
  [Iconfont.Facebook]: "61705",
  [Iconfont.Danger]: "61706",
  [Iconfont.Caret]: "61707",
  [Iconfont.ArrowRight]: "61708",
  [Iconfont.ArrowLeft]: "61709",
};
