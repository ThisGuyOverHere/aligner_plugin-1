import React, {Component} from 'react';
import PropTypes from "prop-types";
import ModalHeader from "../../../Shared/ModalHeader/ModalHeader.component";

class FileFormatsModal extends Component {
    static propTypes = {
        formatModalState: PropTypes.func.isRequired
    };
    constructor(props) {
        super(props);
    }

    render = () => {
        return (
            <div>
                <div className="overlay" onClick={this.props.formatModalState}/>
                <div className="formatsContainer">
                    <ModalHeader modalName={"formats"} close={this.props.formatModalState}/>
                    <div className="content">
                        <div className="format-box">
                            <h3> Office </h3>
                            <ul>
                                <li>
                                    <span className="extdoc">doc</span>
                                </li>
                                <li>
                                    <span className="extdoc">dot</span>
                                </li>
                                <li>
                                    <span className="extdoc">docx</span>
                                </li>
                                <li>
                                    <span className="extdoc">docm</span>
                                </li>
                                <li>
                                    <span className="extdoc">dotx</span>
                                </li>
                                <li>
                                    <span className="extdoc">dotm</span>
                                </li>
                                <li>
                                    <span className="extdoc">rtf</span>
                                </li>
                                <li>
                                    <span className="extdoc">odt</span>
                                </li>
                                <li>
                                    <span className="extdoc">ott</span>
                                </li>
                                <li>
                                    <span className="extpdf">pdf</span>
                                </li>
                                <li>
                                    <span className="exttxt">txt</span>
                                </li>
                                <li>
                                    <span className="extxls">xls</span>
                                </li>
                            </ul>
                            <ul>
                                <li>
                                    <span className="extxls">xlt</span>
                                </li>
                                <li>
                                    <span className="extxls">xlsx</span>
                                </li>
                                <li>
                                    <span className="extxls">xlsm</span>
                                </li>
                                <li>
                                    <span className="extxls">xltx</span>
                                </li>
                                <li>
                                    <span className="extxls">xltm</span>
                                </li>
                                <li>
                                    <span className="extxls">ods</span>
                                </li>
                                <li>
                                    <span className="extxls">ots</span>
                                </li>
                                <li>
                                    <span className="extxls">tsv</span>
                                </li>
                                <li>
                                    <span className="extppt">ppt</span>
                                </li>
                                <li>
                                    <span className="extppt">pps</span>
                                </li>
                                <li>
                                    <span className="extppt">pot</span>
                                </li>
                                <li>
                                    <span className="extppt">pptx</span>
                                </li>
                            </ul>
                            <ul>
                                <li>
                                    <span className="extppt">pptm</span>
                                </li>
                                <li>
                                    <span className="extppt">ppsx</span>
                                </li>
                                <li>
                                    <span className="extppt">ppsm</span>
                                </li>
                                <li>
                                    <span className="extppt">potx</span>
                                </li>
                                <li>
                                    <span className="extppt">potm</span>
                                </li>
                                <li>
                                    <span className="extppt">odp</span>
                                </li>
                                <li>
                                    <span className="extppt">otp</span>
                                </li>
                                <li>
                                    <span className="extxml">xml</span>
                                </li>
                            </ul>
                        </div>
                        <div className="format-box">
                            <h3> Web </h3>
                            <ul>
                                <li>
                                    <span className="exthtm">htm</span>
                                </li>
                                <li>
                                    <span className="exthtm">html</span>
                                </li>
                                <li>
                                    <span className="exthtm">xhtml</span>
                                </li>
                                <li>
                                    <span className="extxml">xml</span>
                                </li>
                                <li>
                                    <span className="extxml">dtd</span>
                                </li>
                                <li>
                                    <span className="extxml">json</span>
                                </li>
                                <li>
                                    <span className="extxml">yaml</span>
                                </li>
                                <li>
                                    <span className="extxml">yml</span>
                                </li>
                            </ul>
                        </div>
                        <div className="format-box">
                            <h3> Scanned Files </h3>
                            <ul>
                                <li>
                                    <span className="extpdf">pdf</span>
                                </li>
                                <li>
                                    <span className="extimg">bmp</span>
                                </li>
                                <li>
                                    <span className="extimg">png</span>
                                </li>
                                <li>
                                    <span className="extimg">gif</span>
                                </li>
                                <li>
                                    <span className="extimg">jpeg</span>
                                </li>
                                <li>
                                    <span className="extimg">jpg</span>
                                </li>
                                <li>
                                    <span className="extimg">tiff</span>
                                </li>
                            </ul>
                        </div>
                        <div className="format-box">
                            <h3> Interchange Formats </h3>
                            <ul>
                                <li>
                                    <span className="extxif">xliff</span>
                                </li>
                                <li>
                                    <span className="extxif">sdlxliff</span>
                                </li>
                                <li>
                                    <span className="extttx">ttx</span>
                                </li>
                                <li>
                                    <span className="extxlf">xlf</span>
                                </li>
                            </ul>
                        </div>
                        <div className="format-box">
                            <h3> Desktop Publishing </h3>
                            <ul>
                                <li>
                                    <span className="extmif">mif</span>
                                </li>
                                <li>
                                    <span className="extidd">idml</span>
                                </li>
                                <li>
                                    <span className="exticml">icml</span>
                                </li>
                                <li>
                                    <span className="extxml">xml</span>
                                </li>
                                <li>
                                    <span className="extdit">dita</span>
                                </li>
                            </ul>
                        </div>
                        <div className="format-box">
                            <h3> Localization </h3>
                            <ul>
                                <li>
                                    <span className="extpro">properties</span>
                                </li>
                                <li>
                                    <span className="extres">resx</span>
                                </li>
                                <li>
                                    <span className="extxml">xml</span>
                                </li>
                                <li>
                                    <span className="extxml">sxml</span>
                                </li>
                                <li>
                                    <span className="extxml">txml</span>
                                </li>
                                <li>
                                    <span className="extdit">dita</span>
                                </li>
                                <li>
                                    <span className="extxml">Android xml</span>
                                </li>
                                <li>
                                    <span className="extstr">strings</span>
                                </li>
                                <li>
                                    <span className="extsrt">srt</span>
                                </li>
                                <li>
                                    <span className="extwix">wix</span>
                                </li>
                                <li>
                                    <span className="extpo">po</span>
                                </li>
                                <li>
                                    <span className="extg">g</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        );
    }
}

export default FileFormatsModal;