import axios from "axios";

const createAxios = (ajaxUrl: string) => {
  return axios.create({
    baseURL: ajaxUrl,
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    withCredentials: true,
  });
};

export default createAxios;
