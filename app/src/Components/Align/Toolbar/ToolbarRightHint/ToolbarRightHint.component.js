import React, {Component} from 'react';
import PropTypes from "prop-types";

class ToolbarRightHintComponent extends Component {

    static propTypes = {
        close: PropTypes.func,
    };

    constructor(props) {
        super(props);
        this.state = {
            hintClasses: ''
        };
    }

    componentDidMount() {
        setTimeout(() => {
            this.setState({
                hintClasses: ' open '
            });
        }, 100)
    }

    componentWillUnmount() {

    }

    render() {
        return (
            <div className={this.state.hintClasses + 'hintModal'}>
                <div className="hintModal-header">
                    <div className="close-btn">
                        <i className="icon close" onClick={this.closeModal}> </i>
                    </div>
                </div>

                <div className="hintModal-body">
                    <h1>Functions & Keyboard shortcut</h1>

                    <div className="shortCutRow">
                        <h4>Align two segments</h4>
                        <ul>
                            <li> - drag a segment and release it over another segment </li>
                            <li> - or select a source and a target segment and press the Align button in the top bar</li>
                            <li className="last"> - or select a source and a target segment and press ALT+A
                                <span>Alt</span>
                                <span>A</span>
                            </li>
                        </ul>
                    </div>

                    <div className="shortCutRow">
                        <h4> Merge two or more segments </h4>
                        <ul>
                            <li> - select two or more segments and press the Merge button in the top bar</li>
                            <li className="last"> - or select two or more segments and press ALT+M
                                <span>Alt</span>
                                <span>M</span>
                            </li>
                        </ul>
                    </div>

                    <div className="shortCutRow">
                        <h4> Split Segment </h4>
                        <ul>
                            <li> - double click on a segment</li>
                            <li> - or select a  segment and press the Split button in the top bar</li>
                            <li className="last"> - or select a segment and press ALT+S
                                <span>Alt</span>
                                <span>S</span>
                            </li>
                        </ul>
                    </div>

                    <div className="shortCutRow">
                        <h4> Switch two segments </h4>
                        <ul>
                            <li> - select two segments and press the Switch button in the top bar</li>
                            <li className="last"> - or select two segments and press ALT+R
                                <span>Alt</span>
                                <span>R</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        );
    }

    closeModal = () => {
        this.setState({
            hintClasses: ''
        });
        setTimeout(() => {
            this.props.close();
        },350);
    }
}

export default ToolbarRightHintComponent;
