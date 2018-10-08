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
                <div className="close-btn">
                    <i className="icon close" onClick={this.closeModal}> </i>
                </div>
                <h1>Functions & Keyboard shortcut</h1>

                <div className="shortCutRow">
                    <h4>Align two segments</h4>
                    <ul>
                        <li> - Drag a segment over another</li>
                        <li> - Select a Source and Target segment and press Align in the top bar</li>
                        <li className="last"> - Select a Source and Target segment and press ALT+A
                            <span>Alt</span>
                            <span>A</span>
                        </li>
                    </ul>
                </div>

                <div className="shortCutRow">
                    <h4> Merge two segments </h4>
                    <ul>
                        <li> - Drag a segment over another of the same type while holding down ALT</li>
                        <li> - Select two segments of the same type and press Merge in the top bar</li>
                        <li className="last"> - Select two segments of the same type and press ALT+M
                            <span>Alt</span>
                            <span>M</span>
                        </li>
                    </ul>
                </div>

                <div className="shortCutRow">
                    <h4> Merge more two segments </h4>
                    <ul>
                        <li> - Select segments of the same type and press Merge in the top bar</li>
                        <li className="last"> - Select segments of the same type and press ALT+M
                            <span>Alt</span>
                            <span>M</span>
                        </li>
                    </ul>
                </div>

                <div className="shortCutRow">
                    <h4> Split Segment </h4>
                    <ul>
                        <li> - Doublick on segment</li>
                        <li> - Select segment and press Align in the top bar</li>
                        <li className="last"> - elect segment and press ALT+S
                            <span>Alt</span>
                            <span>S</span>
                        </li>
                    </ul>
                </div>

                <div className="shortCutRow">
                    <h4> Reverse position of two segments </h4>
                    <ul>
                        <li> - Select two segments of the same type and press Reverse in the top bar</li>
                        <li className="last"> - Select two segments of the same type and press ALT+R
                            <span>Alt</span>
                            <span>R</span>
                        </li>
                    </ul>
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